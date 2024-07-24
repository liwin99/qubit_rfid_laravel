<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('RFID Reader Status') }}
        </h2>
        <div class="my-6" x-data="{ open: false }">
            <div class="inline-flex justify-center gap-10 cursor-pointer border bg-gray-50 rounded-sm p-1 px-2" @click="open = ! open">
                <div>Filter</div>
                <div><i class="fa-solid fa-chevron-down" :class="{ 'fa-chevron-up': open, 'fa-chevron-down': !open }"></i></div>
            </div>
            <fieldset class="border rounded-md p-2 mt-1 hidden" :class="{ 'block': open, 'hidden': !open }">
                <form action="{{ route('rfid.status.index') }}">
                    <div class="flex mt-2 gap-2 flex-wrap">
                        <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                            <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Reader</label>
                            <input type="text" id="name" name="name"
                                value="{{ request()->query()['name'] ?? '' }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                placeholder="Reader...">
                        </div>
                        <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                            <label for="project_name" class="block mb-2 text-sm font-medium text-gray-900">Project</label>
                            <input type="text" id="project_name" name="project_name"
                                value="{{ request()->query()['project_name'] ?? '' }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                placeholder="Project...">
                        </div>
                        <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                            <label for="location_name" class="block mb-2 text-sm font-medium text-gray-900">Location</label>
                            <input type="text" id="location_name" name="location_name"
                                value="{{ request()->query()['location_name'] ?? '' }}"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                placeholder="Location...">
                        </div>
                        <div class="mb-6 w-full md:w-1/4">
                            <label for="sort_by" class="block mb-2 text-sm font-medium text-gray-900">Sort By</label>
                            @php
                                if (isset(request()->query()['sort_by'])) {
                                    $selectedSortBy = request()->query()['sort_by'];
                                } else {
                                    $selectedSortBy = '-';
                                }
                            @endphp
                            <select name="sort_by" id="sort_by"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                <option value="">-</option>
                                <option value="name" {{ $selectedSortBy == 'name' ? 'selected' : '' }}>Reader</option>
                                <option value="project_name" {{ $selectedSortBy == 'project_name' ? 'selected' : '' }}>Project</option>
                            </select>
                        </div>
                        <div class="mb-6 w-full md:w-1/4">
                            <label for="sort_direction" class="block mb-2 text-sm font-medium text-gray-900">Sort
                                Direction</label>
                            @php
                                if (isset(request()->query()['sort_direction'])) {
                                    $selectedSortDirection = request()->query()['sort_direction'];
                                } else {
                                    $selectedSortDirection = '-';
                                }
                            @endphp
                            <select name="sort_direction" id="sort_direction"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                <option value="">-</option>
                                <option value="asc" {{ $selectedSortDirection == 'asc' ? 'selected' : '' }}>Ascending</option>
                                <option value="desc" {{ $selectedSortDirection == 'desc' ? 'selected' : '' }}>Descending</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Submit
                    </button>
                    <a href="{{ route('rfid.status.index') }}"
                        class="inline-block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-min px-5 py-2.5 text-center">
                        Reset
                    </a>
                </form>
            </fieldset>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 min-h-min">
            @if (count($rfidManagements))
                @foreach ($rfidManagements->groupBy('project_name') as $project => $group)
                    @php
                        $readerCount = count($group);
                        $offlineReaderCount = 0;
                        $onlineReaderCount = 0;
                    @endphp

                    <div class="bg-white rounded-lg p-5 py-10 my-8 readers-overview md:p-10">
                        <div class="md:flex md:justify-between md:mb-7 md:gap-5">
                            <div class="md:flex-1">
                                <div>Project</div>
                                <div class="text-3xl font-bold p-3">{{ $project }}</div>
                            </div>
                            <div class="my-2 md:flex-1">
                                <fieldset class="border rounded-lg">
                                    <legend>Rfid Reader Count</legend>
                                    <div class="text-center p-5">{{ $readerCount }}</div>
                                </fieldset>
                            </div>
                            <div class="my-2 md:flex-1">
                                <fieldset class="border rounded-lg">
                                    <legend>Online Rfid Reader Count</legend>
                                    <div class="text-center p-5 online-count">{{ $onlineReaderCount }}</div>
                                </fieldset>
                            </div>
                            <div class="my-2 md:flex-1">
                                <fieldset class="border rounded-lg">
                                    <legend>Offline Rfid Reader Count</legend>
                                    <div class="text-center p-5 offline-count">{{ $offlineReaderCount }}</div>
                                </fieldset>
                            </div>
                        </div>

                        @foreach ($group as $reader)
                            <div class="mt-4 rounded-lg border-gray-200 border p-3 md:flex"
                                data-offline="{{ $offlineReaderCount }}">
                                <div class="flex gap-3 items-center md:w-2/3 md:flex-grow">
                                    <img src="{{ asset('images/rfid.png') }}" alt="rfid-device" width="40px">
                                    <div class="text-lg break-all">
                                        <div>{{ $reader->name }}</div>
                                        <small>{{ $reader->fullName }}</small>
                                    </div>
                                </div>

                                @php
                                    $online = $reader->isOnline($reader->heartbeats);

                                    if (isset($online['isOnline']) && $online['isOnline'] == true) {
                                        $bgColor = 'bg-green-500';
                                        $onlineReaderCount++;
                                    } else {
                                        $bgColor = 'bg-red-600';
                                        $offlineReaderCount++;
                                    }
                                @endphp

                                <div class="flex gap-5 items-center mt-2 md:w-1/3 md:flex-grow md:ml-20">
                                    <div class="w-4 h-4 rounded-full {{ $bgColor }}"></div>
                                    <div>
                                        <div class="text-xs text-gray-700">Last Known Online :</div>
                                        <div>{{ $online['display'] }}</div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div class="end-overview" data-online="{{ $onlineReaderCount }}"
                            data-offline="{{ $offlineReaderCount }}"></div>
                    </div>
                @endforeach
                <!-- Pagination links -->
                <div class="mt-4">
                    {{ $rfidManagements->appends($_GET)->links() }}
                </div>
            @else
                <div class="bg-white p-7 mt-4 shadow-md rounded-lg">
                    No data yet.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

<script>
    window.onload = (event) => {
        let readers = document.querySelectorAll('.readers-overview');

        for (let r of readers) {
            // Reassigning correct offline/online count to overview
            r.querySelector('.online-count').textContent = r.querySelector('.end-overview').dataset.online;
            r.querySelector('.offline-count').textContent = r.querySelector('.end-overview').dataset.offline;
        }
    };
</script>
