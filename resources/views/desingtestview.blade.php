<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Website</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">
    <nav class="fixed top-0 w-full bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <img class="h-8 w-8" src="https://picsum.photos/200/200" alt="Your logo">
                    <div class="hidden md:block">
                        <div class="ml-10 flex items-baseline space-x-4">
                            <a href="#"
                                class="text-gray-800 hover:text-gray-800 px-3 py-2 rounded-md text-sm font-medium">Home</a>
                            <div class="relative group">
                                <a href="#"
                                    class="text-gray-300 hover:text-gray-800 px-3 py-2 rounded-md text-sm font-medium">Services</a>
                                <div
                                    class="absolute left-0 w-full mt-2 origin-top-right rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 group-hover:block">
                                    <div class="py-1" role="menu" aria-orientation="vertical"
                                        aria-labelledby="options-menu">
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            role="menuitem">Service 1</a>
                                        <a href="#"
                                            class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            role="menuitem">Service 2</a>
                                    </div>
                                </div>
                            </div>
                            <a href="#"
                                class="text-gray-300 hover:text-gray-800 px-3 py-2 rounded-md text-sm font-medium">About
                                Us</a>
                            <a href="#"
                                class="text-gray-300 hover:text-gray-800 px-3 py-2 rounded-md text-sm font-medium">Contact</a>
                        </div>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-4 flex items-center md:ml-6">
                        <button class="bg-gray-800 text-white px-3 py-2 rounded-md text-sm font-medium">Login</button>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    <main class="mt-16">
        <div class="card" style="width: 18rem;">
            <img src="https://picsum.photos/200/300" class="card-img-top" alt="Product Image">
            <div class="card-body">
                <h5 class="card-title">Product Name</h5>
                <p class="card-text">Some quick example text to build on the card title and make up the bulk of the
                    card's content.</p>
                <a href="#" class="btn btn-primary">Purchase</a>
            </div>
        </div>

        <div class="w-full overflow-hidden rounded-lg shadow-xs">
            <div class="w-full overflow-x-auto">
                <table class="w-full whitespace-no-wrap">
                    <thead>
                        <tr
                            class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
                            <th class="px-4 py-3">Column 1</th>
                            <th class="px-4 py-3">Column 2</th>
                            <th class="px-4 py-3">Column 3</th>
                            <th class="px-4 py-3">Column 4</th>
                            <th class="px-4 py-3">Column 5</th>
                            <th class="px-4 py-3">Column 6</th>
                            <th class="px-4 py-3">Column 7</th>
                            <th class="px-4 py-3">Column 8</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y">
                        <tr class="text-gray-700">
                            <td class="px-4 py-3"><button
                                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Success
                                </button></td>
                            <td class="px-4 py-3"><button
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Primary
                                </button></td>
                            <td class="px-4 py-3"><button
                                    class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Secondary
                                </button></td>
                            <td class="px-4 py-3">Row 1</td>
                            <td class="px-4 py-3">Row 1</td>
                            <td class="px-4 py-3">Row 1</td>
                            <td class="px-4 py-3">Row 1</td>
                            <td class="px-4 py-3">Row 1</td>
                        </tr>
                        <!-- More rows... -->
                    </tbody>
                </table>
            </div>
        </div>


    </main>
</body>

</html>
