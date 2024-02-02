<?php
header("Content-Type: text/html; charset=UTF-8");

function available_compilers()
{
    return   '<option value="1">x86-64 gcc 13.2 polymorphism</option>'
           . '<option value="2">x86-64 gcc 13.2</option>'
           . '<option value="3">x86-64 gcc 7.5</option>'
    ;
}

function build($compiler_options,$source_code)
{
    if ( empty($source_code) ) return;
    file_put_contents('/tmp/godboltmini_source.cpp', $source_code . "\n");
    $cmd  = '../godbolt/compilers/gnu13_20240131/bin/g++ -o /tmp/godboltmini_executable /tmp/godboltmini_source.cpp ';
    $cmd .= $compiler_options;  // must make safe so that hackers can't wipe my webspace!
    $cmd .= ' 2>&1';
    return shell_exec($cmd);
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sourceCode']))
{
    $sourceCode = $_POST['sourceCode'];
    $compilerOptions = $_POST['compilerOptions'];
    $result = build($compilerOptions, $sourceCode);

    // Return the result as JSON
    echo json_encode(['result' => $result]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GodBolt mini</title>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            overflow: hidden;
            background-color: rgb(248, 249, 250);
        }

        .split-layout {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .top-pane {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: rgb(248, 249, 250);
            border-bottom: 1px solid #ddd;
        }

        /* Adjusted image style in the top pane */
        .top-pane img {
            width: 10%; /* Resize image to 10% of the width of the entire page */
        }

        .border-with-text {
            background-color: rgb(240, 240, 240);
            border: 2px solid rgb(103, 197, 42); /* Green text color */
            padding: 10px;
            box-sizing: border-box;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            border-radius: 10px; /* Add rounded corners to the top pane */
        }

        .main-pane {
            display: flex;
            height: 100%;
        }

        .left-pane {
            overflow: auto;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 10px;
            overflow: auto;
            width: 70%;
        }

        .left-pane .editable-content {
            width: 100%;
            height: 100%;
            box-sizing: border-box;
            resize: none;
            font-size: 16px;
            outline: none;
            white-space: pre-wrap;
            overflow-y: auto;
            border: none;
            padding: 5px;
            line-height: 1.5;
            color: black;
            direction: ltr;
            text-align: left;
            font-family: monospace;
        }

        .right-pane {
            display: flex;
            flex-direction: column;
            overflow: auto;
            flex-grow: 1;
        }

        .upper-right,
        .lower-right {
            flex: 1;
            overflow: auto;
            border-bottom: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }

        #upper-right-textbox, #lower-right-textbox {
            resize: none;
            height: 100%;
            font-family: monospace;
        }

        .compiler-options {
            display: flex;
            align-items: center;
            padding: 10px;
        }

        select {
            margin-right: 10px;
        }

        button {
            background-color: rgb(248, 249, 250);
            font-family: 'Open Sans', sans-serif;
            font-size: 1.0em;
            color: rgb(124,124,125); /* Dark grey color for text */
            border: none;
            cursor: pointer;
            padding: 1px;
            width: auto;
            height: 100%;
            border-radius: 10px; /* Add rounded corners to the top pane */
        }

        /* Button border on hover */
        button:hover {
            border: 1px solid #ddd;
            background-color: rgb(226, 230, 234); /* Change color on hover */
        }

        #compiler-options-textbox {
            display: flex;
            flex-flow: column nowrap;
            flex-grow: 1;
        }

        .resize-handle {
            width: 10px;
            height: 100%;
            background-color: #ddd;
            cursor: ew-resize;
            resize: horizontal;
        }

        .blue-text {
            color: blue !important;
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <div class="top-pane">
            <!-- Adjusted image style in the top pane -->
            <img src="compiler_explorer_mini.png" alt="Compiler Explorer Mini Logo">
            <div class="border-with-text">Look after yourself, and, if you can, foster a child too.</div>
            <div>
                <button onclick="shareButtonClick()">Share &blacktriangledown;</button>
                <button>Policies &blacktriangledown;</button>
                <button>Other &blacktriangledown;</button>
            </div>
        </div>

        <div class="main-pane">
            <div class="left-pane">
                <div id="output-content" class="editable-content" contenteditable="true" spellcheck="false" style="display: inline-block" oninput="updateContent()"><span class="blue-text">int</span> main(<span class="blue-text">void</span>)<br>{<br>    <span class="blue-text">return</span>;<br>}<br></div>
            </div>
            <div class="resize-handle" onmousedown="startResizing()"></div>
            <div class="right-pane">
                <div class="upper-right">
                    <div class="compiler-options">
                        <select id="compiler-select"><?php echo available_compilers();?></select>
                        <input type="text" id="compiler-options-textbox" value="-std=c++23 -pedantic -Wall -Wextra">
                    </div>
                    <textarea id="upper-right-textbox" style="height: 100%;"></textarea>
                </div>

                <div class="lower-right">
                    <textarea id="lower-right-textbox" style="height: 100%;"></textarea>
                </div>
            </div>

        </div>
        <script>
            var isResizing = false;
            var lastX;

            function startResizing(e) {
                isResizing = true;
                lastX = e.clientX;
            }

            function stopResizing() {
                isResizing = false;
            }

            function handleMouseMove(e) {
                if (isResizing) {
                    const offset = e.clientX - lastX;
                    lastX = e.clientX;
                    resizePane(offset);
                }
            }

            function resizePane(offset) {
                const leftPane = document.querySelector('.left-pane');
                const rightPane = document.querySelector('.right-pane');
                const newWidth = leftPane.offsetWidth + offset;

                leftPane.style.width = `${newWidth}px`;
                rightPane.style.width = `calc(100% - ${newWidth}px)`;
            }

            document.addEventListener('mouseup', stopResizing);
            document.addEventListener('mousemove', handleMouseMove);
        </script>
        <script>
            function GetCaretPosition(el) {
                var caretPos = 0;
                var sel, range;

                if (window.getSelection) {
                    sel = window.getSelection();
                    if (sel.rangeCount) {
                        range = sel.getRangeAt(0);
                        var preCaretRange = range.cloneRange();
                        preCaretRange.selectNodeContents(el);
                        preCaretRange.setEnd(range.endContainer, range.endOffset);
                        caretPos = preCaretRange.toString().length;
                    }
                } else if (document.selection && document.selection.createRange) {
                    range = document.selection.createRange();
                    var textRange = el.createTextRange();
                    textRange.setEndPoint("EndToStart", range);
                    caretPos = textRange.text.length;
                }

                return caretPos;
            }
            function SetCaretPosition(el, pos) {
                // Loop through all child nodes
                for (var node of el.childNodes) {
                    if (node.nodeType == 3) { // we have a text node
                        if (node.length >= pos) {
                            // finally add our range
                            var range = document.createRange(),
                                sel = window.getSelection();
                            range.setStart(node, pos);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);

                            // If the cursor position is at the end, set it after the last visible character
                            if (pos === node.length) {
                                range.setStartAfter(node);
                                range.collapse(true);
                                sel.removeAllRanges();
                                sel.addRange(range);
                            }

                            return -1; // we are done
                        } else {
                            pos -= node.length;
                        }
                    } else {
                        pos = SetCaretPosition(node, pos);
                        if (pos == -1) {
                            return -1; // no need to finish the for loop
                        }
                    }
                }
                return pos; // needed because of recursion stuff
            }
            function insertTextAtCursor(text)
            {
                var sel, range;
                if (window.getSelection) {
                    sel = window.getSelection();
                    if (sel.getRangeAt && sel.rangeCount) {
                        range = sel.getRangeAt(0);
                        range.deleteContents();
                        range.insertNode( document.createTextNode(text) );
                    }
                } else if (document.selection && document.selection.createRange) {
                    document.selection.createRange().text = text;
                }
            }
            document.addEventListener('DOMContentLoaded', function ()
            {
                document.addEventListener('keydown', function (event)
                {
                    if ( document.activeElement.id != 'output-content' ) return;

                    if ( event.key === 'Tab' || event.keyCode === 9 )
                    {
                        event.preventDefault();
                        document.execCommand('insertText', false, '    ');
                    }
                    else if ( event.key === '<' )
                    {
                        event.preventDefault();
                        //document.execCommand('insertText', false, "&lt; ");
                    }
                    else if ( event.key === '>' )
                    {
                        event.preventDefault();
                        //document.execCommand('insertText', false, "&gt; ");
                    }
                });

                document.getElementById('output-content').addEventListener('keydown', function (event)
                {
                    if ( event.key === 'Enter' || event.keyCode === 13 )
                    {
                        event.preventDefault();
                        const pos = GetCaretPosition(this);
                        var text = this.innerText || this.textContent;
                        let is_at_end = ( pos >= (text.length - 1) );
                        
                        if ( is_at_end )
                        {
                            insertTextAtCursor('\n ');
                            commonUpdateContent(this, GetCaretPosition(this) - 1);
                        }
                        else
                        {
                            insertTextAtCursor('\n');
                            commonUpdateContent(this, GetCaretPosition(this));
                        }

                        this.focus();
                    }
                });
            });
            function commonUpdateContent(textbox,cursor_position)
            { 
                const wordsToHighlight = [
                    'alignas', 'alignof', 'and', 'and_eq', 'asm', 'atomic_cancel',
                    'atomic_commit', 'atomic_noexcept', 'auto', 'bitand', 'bitor',
                    'bool', 'break', 'case', 'catch', 'char', 'char8_t', 'char16_t',
                    'char32_t', /*'class',*/ 'compl', 'concept', 'const', 'consteval',
                    'constexpr', 'constinit', 'const_cast', 'continue', 'co_await',
                    'co_return', 'co_yield', 'decltype', 'default', 'delete', 'do',
                    'double', 'dynamic_cast', 'else', 'enum', 'explicit', 'export',
                    'extern', 'false', 'float', 'for', 'friend', 'goto', 'if', 'inline',
                    'int', 'long', 'mutable', 'namespace', 'new', 'noexcept', 'not',
                    'not_eq', 'nullptr', 'operator', 'or', 'or_eq', 'private',
                    'protected', 'public', 'reflexpr', 'register', 'reinterpret_cast',
                    'requires', 'return', 'short', 'signed', 'sizeof', 'static',
                    'static_assert', 'static_cast', 'struct', 'switch', 'synchronized',
                    'template', 'this', 'thread_local', 'throw', 'true', 'try',
                    'typedef', 'typeid', 'typename', 'union', 'unsigned', 'using',
                    'virtual', 'void', 'volatile', 'wchar_t', 'while', 'xor', 'xor_eq'
                ];

                const selection = window.getSelection();

                let content = textbox.innerText;

                wordsToHighlight.forEach(word => {
                    const regex = new RegExp(`\\b${word}\\b`, 'gi');
                    content = content.replace(regex, match => `<span class="blue-text">${match}</span>`);
                });

                // (2) Set the content
                textbox.innerHTML = content

                SetCaretPosition(textbox, cursor_position);
            }
            function updateContent()
            {
                const outputContent = document.getElementById('output-content');
                commonUpdateContent( outputContent, GetCaretPosition(outputContent) );
            }
            function shareButtonClick() {
                var compilerOptions = document.getElementById('compiler-options-textbox').value;
                var sourceCode = document.getElementById('output-content').innerText;
                // Send the user input to PHP using fetch
                fetch(window.location.href, {
                    method: 'POST',
                    body: new URLSearchParams({ compilerOptions: compilerOptions, sourceCode: sourceCode }),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                })
                .then(response => response.json())
                .then(data => {
                    const compilerOutput = document.getElementById('upper-right-textbox');
                    compilerOutput.innerHTML = data.result;
                });
            }
            document.addEventListener('DOMContentLoaded', function () {
                // Get the height of the top-pane
                const topPane = document.querySelector('.top-pane');
                const topPaneHeight = topPane.offsetHeight;

                // Set the height of the buttons to match the top-pane
                const buttons = document.querySelectorAll('.top-pane button');
                buttons.forEach(button => {
                    button.style.height = 1.0*topPaneHeight + 'px';
                    button.style.width  = 1.5*topPaneHeight + 'px';
                });
            });
        </script>
    </div>
</body>
</html>

