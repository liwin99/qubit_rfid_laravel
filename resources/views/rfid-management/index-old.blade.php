<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('RFID Reader Management') }}
        </h2>
        <fieldset class="border rounded-md p-2 mt-6">
            <legend class="">Filter</legend>
            <form action="{{ route('rfid.management.index') }}">
                <div class="flex mt-2 gap-2 flex-wrap">
                    <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                        <label for="reader_name" class="block mb-2 text-sm font-medium text-gray-900">Reader</label>
                        <input type="text" id="reader_name" name="reader_name"
                            value="{{ request()->query()['reader_name'] ?? '' }}"
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
                        <label for="location_1_name" class="block mb-2 text-sm font-medium text-gray-900">Location
                            1</label>
                        <input type="text" id="location_1_name" name="location_1_name"
                            value="{{ request()->query()['location_1_name'] ?? '' }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                            placeholder="Location 1...">
                    </div>
                    <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                        <label for="location_2_name" class="block mb-2 text-sm font-medium text-gray-900">Location
                            2</label>
                        <input type="text" id="location_2_name" name="location_2_name"
                            value="{{ request()->query()['location_2_name'] ?? '' }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                            placeholder="Location 2...">
                    </div>
                    <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                        <label for="location_3_name" class="block mb-2 text-sm font-medium text-gray-900">Location
                            3</label>
                        <input type="text" id="location_3_name" name="location_3_name"
                            value="{{ request()->query()['location_3_name'] ?? '' }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                            placeholder="Location 3...">
                    </div>
                    <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                        <label for="location_4_name" class="block mb-2 text-sm font-medium text-gray-900">Location
                            4</label>
                        <input type="text" id="location_4_name" name="location_4_name"
                            value="{{ request()->query()['location_4_name'] ?? '' }}"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                            placeholder="Location 4...">
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
                        <select name="sort_by" id="sort_by" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                            <option value="">-</option>
                            <option value="reader_id" {{ $selectedSortBy == 'reader_id' ? 'selected' : '' }}>Reader</option>
                            <option value="project_id" {{ $selectedSortBy == 'project_id' ? 'selected' : '' }}>Project</option>
                            <option value="location_1_id" {{ $selectedSortBy == 'location_1_id' ? 'selected' : '' }}>Location 1</option>
                            <option value="location_2_id" {{ $selectedSortBy == 'location_2_id' ? 'selected' : '' }}>Location 2</option>
                            <option value="location_3_id" {{ $selectedSortBy == 'location_3_id' ? 'selected' : '' }}>Location 3</option>
                            <option value="location_4_id" {{ $selectedSortBy == 'location_4_id' ? 'selected' : '' }}>Location 4</option>
                        </select>
                    </div>
                    <div class="mb-6 w-full md:w-1/4">
                        <label for="sort_direction" class="block mb-2 text-sm font-medium text-gray-900">Sort Direction</label>
                        @php
                            if (isset(request()->query()['sort_direction'])) {
                                $selectedSortDirection = request()->query()['sort_direction'];
                            } else {
                                $selectedSortDirection = '-';
                            }
                        @endphp
                        <select name="sort_direction" id="sort_direction" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
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
                <div
                    class="inline-block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-min px-5 py-2.5 text-center">
                    <a href="{{ route('rfid.management.index') }}">
                        Reset
                    </a>
                </div>
            </form>

        </fieldset>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 min-h-min">

                @if (count($errors->storeNewReader))
                    <div x-data="{ showCreateError: true }" @cancel_management.window="showCreateError = false" x-show="showCreateError">
                        <x-custom-validation-errors :errors="$errors->storeNewReader->all()" />
                    </div>
                @endif

                @if (count($errors->updateExistingReader))
                    <div x-data="{ showUpdateError: true }" @cancel_edit_management.window="showUpdateError = false" x-show="showUpdateError">
                        <x-custom-validation-errors :errors="$errors->updateExistingReader->all()" />
                    </div>
                @endif

                <div class="text-right"
                    x-data="{ showCreateButton: true }"
                    x-init="showCreateButton = <?php if($errors->storeNewReader->any()) { echo 'false'; } else { echo 'true'; } ?>">
                    <button type="button" x-show="showCreateButton" x-cloak @cancel_management.window="showCreateButton = true" x-transition
                        x-on:click="$dispatch('add_new_management'); showCreateButton = false""
                        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        Create New
                    </button>
                </div>

                <x-create-management />

            @if (count($rfidManagements))
                @foreach ($rfidManagements as $rfidManagement)
                    <x-show-management :rfid-management="$rfidManagement"/>
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
