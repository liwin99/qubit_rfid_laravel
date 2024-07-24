<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('RFID Reader Pairing') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="bg-white p-4 py-10 rounded-lg">
                <x-validation-errors class="mb-8" />

                <form action="{{ route('rfid.pairing.store') }}" method="POST">
                    @csrf
                    <div class="md:flex md:justify-between md:gap-5">
                        <div class="mb-5 md:flex-grow">
                            <label for="reader_1_id" class="block mb-2 text-sm font-medium text-gray-900">Reader 1</label>
                            @php
                                $selectedReader1 = old('reader_1_id') ?? null;
                            @endphp
                            <select name="reader_1_id" id="reader_1_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                @foreach ($readers as $reader)
                                    <option value="{{ $reader->id }}"
                                        {{ $selectedReader1 == $reader->id ? 'selected' : '' }}>{{ $reader->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-10 md:mb-5 md:flex-grow md:mr-7">
                            <label for="reader_1_position" class="block mb-2 text-sm font-medium text-gray-900">Reader 1 Position</label>
                            @php
                                $selectedReader1 = old('reader_1_position') ?? null;
                            @endphp
                            <select name="reader_1_position" id="reader_1_position"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                <option value="1" {{ $selectedReader1 == '1' ? 'selected' : '' }}>1 : Closer to exit</option>
                                <option value="2" {{ $selectedReader1 == '2' ? 'selected' : '' }}>2 : Closer to site</option>
                            </select>
                        </div>
                        <div class="mb-5 md:flex-grow md:ml-7">
                            <label for="reader_2_id" class="block mb-2 text-sm font-medium text-gray-900">Reader 2</label>
                            @php
                                $selectedReader2 = old('reader_2_id') ?? null;
                            @endphp
                            <select name="reader_2_id" id="reader_2_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                @foreach ($readers as $reader)
                                    <option value="{{ $reader->id }}"
                                        {{ $selectedReader2 == $reader->id ? 'selected' : '' }}>{{ $reader->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-5 md:flex-grow">
                            <label for="reader_2_position" class="block mb-2 text-sm font-medium text-gray-900">Reader 2 Position</label>
                            @php
                                $selectedReader2 = old('reader_2_position') ?? null;
                            @endphp
                            <select name="reader_2_position" id="reader_2_position"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                <option value="2" {{ $selectedReader2 == '2' ? 'selected' : '' }}>2 : Closer to site</option>
                                <option value="1" {{ $selectedReader2 == '1' ? 'selected' : '' }}>1 : Closer to exit</option>
                            </select>
                        </div>
                    </div>
                    <p class="text-gray-600 text-xs">* Readers cannot be duplicated.</p>
                    <p class="text-gray-600 text-xs">* Readers must not already been assigned.</p>
                    <p class="text-gray-600 text-xs mb-5">* Readers position cannot be duplicated.</p>
                    <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
