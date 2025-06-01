<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Product &raquo; {{ $product->name }} &raquo; Gallery
        </h2>
    </x-slot>

    {{-- Injection script DataTable --}}
    @push('script')
        <script>
            $(document).ready(function() {
                $('#crudTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: '{!! url()->current() !!}',
                    columns: [{
                            data: 'id',
                            name: 'id',
                            width: '5%'
                        },
                        {
                            data: 'url',
                            name: 'url',
                            render: function(data, type, row, meta) {
                                return `<div class="w-[50px] sm:w-[70px] md:w-[100px] max-w-full">
                                            <img src="${data}" class="w-full h-auto rounded-md object-cover" />
                                         </div>`;
                            },
                        },
                        {
                            data: 'is_featured',
                            name: 'is_featured',
                            render: function(data, type, row, meta) {
                                return `<input type="checkbox" class="form-checkbox h-5 w-5 text-green-600" ${data ? 'checked' : ''} disabled>`;
                            },
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            width: '25%',
                            render: function(data, type, row, meta) {
                                return data;
                            },
                        },
                    ]
                });
            });
        </script>
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-10">
                <a href="{{ route('dashboard.product.gallery.create', $product->id) }}"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow-lg">
                    + Create Gallery
                </a>
            </div>
            <div class="shadow overflow-hidden sm:rounded-md bg-white">
                <div class="px-4 py-5 bg-white sm:p-6">
                    <table id="crudTable" class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Photo</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Featured</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            {{-- DataTable will populate this --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
