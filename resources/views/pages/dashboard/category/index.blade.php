<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Category') }}
        </h2>
    </x-slot>

    {{-- Injeksi script DataTable --}}
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
                            data: 'name',
                            name: 'name'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false,
                            width: '25%',
                            render: function(data, type, row, meta) {
                                return data;
                            }
                        },
                    ]
                });
            });
        </script>
    @endpush

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Tombol di luar box tabel --}}
            <div class="mb-6">
                <a href="{{ route('dashboard.category.create') }}"
                    class="bg-green-500 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg shadow">
                    + Create Category
                </a>
            </div>

            {{-- Tabel dengan border dan rounded --}}
            <div class="shadow overflow-hidden sm:rounded-lg bg-white">
                <div class="px-4 py-5 sm:p-6">
                    <table id="crudTable" class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nama</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Akan diisi oleh DataTables --}}
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
