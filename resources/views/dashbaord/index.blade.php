<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Widget Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }

        nav {
            background-color: #333;
            padding: 10px 20px;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
            font-weight: 600;
        }

        nav a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .controls {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .controls label {
            margin-right: 10px;
            font-weight: 500;
        }

        .controls select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            margin-right: 15px;
        }

        .controls button {
            padding: 8px 16px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .controls button:hover {
            background-color: #45a049;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        @media (max-width: 992px) {
            .grid-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .grid-container {
                grid-template-columns: 1fr;
            }
        }

        .widget {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
            border: 1px solid #ddd;
            min-height: 200px;
        }

        .widget-header {
            padding: 10px 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .widget-content {
            padding: 15px;
        }

        .widget-placeholder {
            background-color: #f0f0f0;
            border: 2px dashed #ccc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #888;
            font-weight: 500;
        }

        .widget-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }

        .widget-button {
            background-color: #eee;
            border: none;
            border-radius: 4px;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .widget-button:hover {
            background-color: #ddd;
        }

        .grid-slot-highlight {
            border: 2px solid #4CAF50 !important;
            box-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }
    </style>
</head>
<body>
<x-app-layout>
    <div class="container">
        <h1>Groep H Dashboard</h1>

        <div class="controls">
            <label for="grid-position">Position:</label>
            <select id="grid-position">
                <option value="0">Position 1</option>
                <option value="1">Position 2</option>
                <option value="2">Position 3</option>
                <option value="3">Position 4</option>
                <option value="4">Position 5</option>
                <option value="5">Position 6</option>
                <option value="6">Position 7</option>
                <option value="7">Position 8</option>
                <option value="8">Position 9</option>
            </select>

            <label for="widget-type">Widget:</label>
            <select id="widget-type">
                <option value="0">Dummy Widget 1</option>
                <option value="1">Dummy Widget 2</option>
                <option value="2">Dummy Widget 3</option>
                <option value="3">Dummy Widget 4</option>
                <option value="4">Dummy Widget 5</option>
                <option value="5">Dummy Widget 6</option>
                <option value="6">Dummy Widget 7</option>
                <option value="7">Dummy Widget 8</option>
                <option value="8">Dummy Widget 9</option>
            </select>

            <button onclick="setWidget()">Set Widget</button>
            <button onclick="saveLayout()">Save Layout</button>
            <button onclick="resetLayout()">Reset Layout</button>
        </div>

        <div id="grid-container" class="grid-container">
        </div>
    </div>
</x-app-layout>

<script>
    const widgets = [
        { id: 0, name: "Dummy Widget 1", content: "<div>This is Dummy Widget 1</div>" },
        { id: 1, name: "Dummy Widget 2", content: "<div>This is Dummy Widget 2</div>" },
        { id: 2, name: "Dummy Widget 3", content: "<div>This is Dummy Widget 3</div>" },
        { id: 3, name: "Dummy Widget 4", content: "<div>This is Dummy Widget 4</div>" },
        { id: 4, name: "Dummy Widget 5", content: "<div>This is Dummy Widget 5</div>" },
        { id: 5, name: "Dummy Widget 6", content: "<div>This is Dummy Widget 6</div>" },
        { id: 6, name: "Dummy Widget 7", content: "<div>This is Dummy Widget 7</div>" },
        { id: 7, name: "Dummy Widget 8", content: "<div>This is Dummy Widget 8</div>" },
        { id: 8, name: "Dummy Widget 9", content: "<div>This is Dummy Widget 9</div>" }
    ];

    let gridLayout = [0, 1, 2, 3, 4, 5, 6, 7, 8];

    function initializeGrid() {
        const savedLayout = localStorage.getItem('widgetGridLayout');
        if (savedLayout) {
            gridLayout = JSON.parse(savedLayout);
        }

        const gridContainer = document.getElementById('grid-container');
        gridContainer.innerHTML = '';

        for (let i = 0; i < 9; i++) {
            const widgetId = gridLayout[i];
            const widget = widgets[widgetId];

            const widgetElement = document.createElement('div');
            widgetElement.className = 'widget';
            widgetElement.dataset.position = i;
            widgetElement.dataset.widgetId = widgetId;

            widgetElement.innerHTML = `
                    <div class="widget-header">
                        ${widget.name}
                    </div>
                    <div class="widget-content">
                        ${widget.content}
                    </div>
                `;

            gridContainer.appendChild(widgetElement);
        }
    }

    function setWidget() {
        const positionSelect = document.getElementById('grid-position');
        const widgetSelect = document.getElementById('widget-type');

        const position = parseInt(positionSelect.value);
        const widgetId = parseInt(widgetSelect.value);

        gridLayout[position] = widgetId;
        initializeGrid();
        highlightGridPosition(position);
    }

    function saveLayout() {
        localStorage.setItem('widgetGridLayout', JSON.stringify(gridLayout));
        alert('Layout saved successfully!');
    }

    function resetLayout() {
        gridLayout = [0, 1, 2, 3, 4, 5, 6, 7, 8];
        localStorage.removeItem('widgetGridLayout');
        initializeGrid();
        alert('Layout reset to default!');
    }

    function highlightGridPosition(position) {
        const gridContainer = document.getElementById('grid-container');
        const widgets = gridContainer.querySelectorAll('.widget');

        widgets.forEach(widget => {
            widget.classList.remove('grid-slot-highlight');
        });

        if (widgets[position]) {
            widgets[position].classList.add('grid-slot-highlight');
            setTimeout(() => {
                widgets[position].classList.remove('grid-slot-highlight');
            }, 2000);
        }
    }

    document.addEventListener('DOMContentLoaded', initializeGrid);
</script>
</body>
</html>
