<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Widget Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-5">
<x-app-layout>
    <div class="container">
        <h1 class="text-2xl font-bold text-gray-800 mb-5">Groep H Dashboard</h1>

        <div class="bg-white p-4 rounded-lg shadow mb-5 flex flex-wrap gap-3 items-center">
            <label for="grid-position" class="font-medium">Position:</label>
            <select id="grid-position" class="p-2 border rounded">
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

            <label for="widget-type" class="font-medium">Widget:</label>
            <select id="widget-type" class="p-2 border rounded">
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

            <button onclick="setWidget()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Set Widget</button>
            <button onclick="saveLayout()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Save Layout</button>
            <button onclick="resetLayout()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Reset Layout</button>
        </div>

        <div id="grid-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Grid positions will be populated with JavaScript -->
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
                widgetElement.className = 'bg-white rounded-lg shadow border p-4';
                widgetElement.dataset.position = i;
                widgetElement.dataset.widgetId = widgetId;

                widgetElement.innerHTML = `
                        <div class="font-semibold bg-gray-100 p-2 mb-2">${widget.name}</div>
                        <div>${widget.content}</div>
                    `;

                gridContainer.appendChild(widgetElement);
            }
        }

        function setWidget() {
            const position = parseInt(document.getElementById('grid-position').value);
            const widgetId = parseInt(document.getElementById('widget-type').value);
            gridLayout[position] = widgetId;
            initializeGrid();
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

        document.addEventListener('DOMContentLoaded', initializeGrid);
    </script>
</body>
</html>
