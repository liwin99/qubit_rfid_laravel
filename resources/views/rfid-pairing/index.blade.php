<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('RFID Reader Pairing') }}
        </h2>
        <div class="my-6" x-data="{ open: false }">
            <div class="inline-flex justify-center gap-10 cursor-pointer border bg-gray-50 rounded-sm p-1 px-2" @click="open = ! open">
                <div>Filter</div>
                <div><i class="fa-solid fa-chevron-down" :class="{ 'fa-chevron-up': open, 'fa-chevron-down': !open }"></i></div>
            </div>
            <fieldset class="border rounded-md p-2 mt-1 hidden" :class="{ 'block': open, 'hidden': !open }">
                <form action="{{ route('rfid.pairing.index') }}">
                    <div class="flex mt-2 gap-2">
                        <div class="mb-6 w-1/2 md:w-1/3">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Name</label>
                            <input type="text" id="name" name="name"
                                value="{{ request()->query()['name'] ?? '' }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                placeholder="Name...">
                        </div>
                    </div>
                    <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Submit
                    </button>
                    <a href="{{ route('rfid.pairing.index') }}"
                        class="inline-block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-min px-5 py-2.5 text-center">
                        Reset
                    </a>
                </form>
            </fieldset>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-right">
                <a href="{{ route('rfid.pairing.create') }}" class="inline-block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-auto px-5 py-2.5 text-center">
                    Create New
                </a>
            </div>

            @if (count($rfidReaderPairings))
                <div class="relative overflow-x-auto shadow-md rounded-lg mt-4">
                    @php
                        $sort_direction = request()->query()['sort_direction'] ?? '';
                        $sort_by = request()->query()['sort_by'] ?? '';

                        $get_params = [];

                        if ($sort_direction === 'asc' || $sort_direction === '') {
                            $get_params['sort_direction'] = 'desc';
                            $sort_caret = 'fa-sort-up';
                        } elseif ($sort_direction === 'desc') {
                            $get_params['sort_direction'] = 'asc';
                            $sort_caret = 'fa-sort-down';
                        }
                    @endphp
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3">
                                    <a
                                        href="{{ route('rfid.pairing.index', array_merge(request()->query(), ['sort_by' => 'pair_id'], $get_params)) }}">
                                        <span>Id</span>
                                        <i class="fa-solid {{ $sort_by == 'pair_id' ? $sort_caret : 'fa-sort' }} ml-2"></i>
                                    </a>
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Reader 1 Name
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Reader 1 Position
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Reader 2 Name
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    Reader 2 Position
                                </th>
                                <th scope="col" class="px-6 py-3">
                                    <span class="sr-only">Edit</span>
                                </th>
                            </tr>
                        </thead>
                        @php
                            $groupByPairings = $rfidReaderPairings->groupBy('pair_id');
                        @endphp
                        <tbody>
                            @foreach ($groupByPairings as $pairId => $currentPair)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                        {{ $pairId }}
                                    </th>
                                    @foreach ($currentPair as $current)
                                        <td class="px-6 py-4">
                                            {{ $current->reader->name }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ \App\Models\RfidReaderPairing::POSITION_MAPPING[$current->reader_position] }}
                                        </td>
                                    @endforeach
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('rfid.pairing.edit', ['pairId' => $pairId]) }}"
                                            class="font-medium text-blue-600 hover:underline">Edit</a>
                                        <form
                                            action="{{ route('rfid.pairing.destroy', ['pairId' => $pairId]) }}"
                                            method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="font-medium text-red-600 hover:underline"
                                                onclick="return confirm('Are you sure you want to delete?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <!-- Pagination links -->
                <div class="mt-4">
                    {{ $rfidReaderPairings->appends($_GET)->links() }}
                </div>
            @else
                <div class="bg-white p-7 mt-4 shadow-md rounded-lg">
                    No data yet.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
