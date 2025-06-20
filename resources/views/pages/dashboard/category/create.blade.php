<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {!! __('Category &raquo; Create') !!}
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- ALERT ERROR & FORM dibungkus dalam satu container yang sama --}}
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- ALERT ERROR --}}
            @if ($errors->any())
                <div class="mb-5" role="alert">
                    <div class="bg-red-500 text-white font-bold rounded-t px-4 py-2">
                        {{ __('Whoops! Something went wrong.') }}
                    </div>
                    <div class="border border-t-0 border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700">
                        <p>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        </p>
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- FORM --}}
            <form class="w-full" action="{{ route('dashboard.category.store') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="flex flex-wrap -mx-3 mb-6">
                    <div class="w-full px-3">
                        <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2"
                            for="grid-last-name">
                            {{ __('Name') }}
                        </label>
                        <input value="{{ old('name') }}" name="name"
                            class="appearance-none block w-full bg-gray-200 text-gray-700 border border-gray-200 rounded py-3 px-4 leading-tight focus:outline-none focus:bg-white focus:border-gray-500"
                            id="grid-last-name" type="text" placeholder="Category Name">
                    </div>
                </div>
                <div class="flex flex-wrap -mx-3 mb-6">
                    <div class="w-full px-3 text-right">
                        <button type="submit"
                            class="shadow-lg bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Save Category
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
