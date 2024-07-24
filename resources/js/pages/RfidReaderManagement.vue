<script>
import Spinner from './../components/Spinner.vue'
import Pagination from './../components/Pagination.vue'
import Banner from './../components/Banner.vue'
import SelectDropdown from './../components/SelectDropdown.vue'
import ShowManagement from './../components/ShowManagement.vue'
import CreateManagement from './../components/CreateManagement.vue'

export default {
    data() {
        return {
            filter: {
                name: null,
                project_name: null,
                location_name: null,
                sort_by: '',
                sort_direction: '',
            },
            rfidManagements: {},
            rfidManagementsPagination: {},
            isLoading: false,
            currentPage: 1,
            bannerShow: false,
            bannerMessage: '',
            bannerStyle: 'success',
            isFilterCollapse: true,
        };
    },
    components: {
        Pagination,
        Banner,
        Spinner,
        SelectDropdown,
        ShowManagement,
        CreateManagement,
    },
    created() {
        this.getData();
    },
    methods: {
        async getData(params = {}) {
            this.isLoading = true;

            try {
                const res = await axios.get('/ajax/get-managements', { params });
                this.rfidManagements = res.data.data;
                delete res.data.data;
                this.rfidManagementsPagination = res.data;
            } catch (error) {
                console.log(error);
                this.rfidManagements = [];
            } finally {
                setTimeout(() => {
                    this.isLoading = false
                }, 150);
            }
        },
        setCurrentPage(page) {
            this.currentPage = page
            this.getData({ ...this.filter, ...{ page } })
        },
        handleCreated() {
            this.getData({ ...this.filter, ...{ page: this.currentPage } });
            this.bannerMessage = "Reader created!"
            this.bannerShow = true
            setTimeout(() => {
                this.bannerShow = false;
            }, 1500);
        },
        filterSearch() {
            this.getData({ ...this.filter, ...{ page: this.currentPage } })
        },
        closeBanner() {
            this.bannerShow = false;
        },
        handleDeleted() {
            this.getData({ ...this.filter, ...{ page: this.currentPage } });
            this.bannerMessage = "Reader deleted!"
            this.bannerShow = true
            setTimeout(() => {
                this.bannerShow = false;
            }, 1500);
        },
        handleUpdated() {
            this.bannerMessage = "Reader updated!"
            this.bannerShow = true
            setTimeout(() => {
                this.bannerShow = false;
            }, 1500);
        },
        handleDeleteManagementError(message) {
            this.bannerMessage = message
            this.bannerStyle = 'danger'
            this.bannerShow = true
            setTimeout(() => {
                this.bannerShow = false;
                this.bannerStyle = 'success'
            }, 1500);
        },
        invertFilter() {
            this.isFilterCollapse = !this.isFilterCollapse
        },
    },
}
</script>

<template>
    <Transition name="fade">
        <Banner :initial-message="bannerMessage" v-if="bannerShow" :key="bannerShow" @dismiss-banner="closeBanner" :initial-style="bannerStyle" />
    </Transition>

    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                RFID Reader Management
            </h2>
            <div class="my-6">
                <div class="inline-flex justify-center gap-10 cursor-pointer border bg-gray-50 rounded-sm p-1 px-2" @click="invertFilter">
                    <div>Filter</div>
                    <div><i class="fa-solid" :class="{ 'fa-chevron-up': !isFilterCollapse, 'fa-chevron-down': isFilterCollapse }"></i></div>
                </div>
                    <fieldset class="border rounded-md p-2 mt-1" :class="{ 'block': !isFilterCollapse, 'hidden': isFilterCollapse }">
                        <form @submit.prevent="filterSearch">
                            <div class="flex mt-2 gap-2 flex-wrap">
                                <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                                    <label for="reader_name" class="block mb-2 text-sm font-medium text-gray-900">Reader</label>
                                    <input type="text" id="reader_name" name="reader_name" v-model="filter.name"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                        placeholder="Reader...">
                                </div>
                                <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                                    <label for="project_name" class="block mb-2 text-sm font-medium text-gray-900">Project</label>
                                    <input type="text" id="project_name" name="project_name" v-model="filter.project_name"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                        placeholder="Project...">
                                </div>
                                <div class="mb-6 w-full md:w-1/4 md:flex-grow">
                                    <label for="location_name" class="block mb-2 text-sm font-medium text-gray-900">Location</label>
                                    <input type="text" id="location_name" name="location_name" v-model="filter.location_name"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5"
                                        placeholder="Location...">
                                </div>
                                <div class="mb-6 w-full md:w-1/4">
                                    <label for="sort_by" class="block mb-2 text-sm font-medium text-gray-900">Sort By</label>
                                    <select name="sort_by" id="sort_by" v-model="filter.sort_by"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                        <option value="" selected>-</option>
                                        <option value="name">Reader</option>
                                        <option value="project_name">Project</option>
                                    </select>
                                </div>
                                <div class="mb-6 w-full md:w-1/4">
                                    <label for="sort_direction" class="block mb-2 text-sm font-medium text-gray-900">Sort
                                        Direction</label>
                                    <select name="sort_direction" id="sort_direction" v-model="filter.sort_direction"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-2.5">
                                        <option value="">-</option>
                                        <option value="asc">Ascending</option>
                                        <option value="desc">Descending</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit"
                                class="text-white mr-1 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                                Submit
                            </button>
                            <a href="/rfid-management"
                                class="inline-block text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-min px-5 py-2.5 text-center">
                                Reset
                            </a>
                        </form>
                    </fieldset>
            </div>
        </div>
    </header>

    <main>
        <div class="py-8">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 min-h-min">
                <CreateManagement @created-new-management="handleCreated" />

                <Spinner v-if="isLoading" />
                <div v-else>
                    <div v-if="rfidManagements.length > 0">
                        <TransitionGroup name="fade">
                            <div v-for="rfidManagement in rfidManagements" :key="rfidManagement.id" class="mt-4">
                                <ShowManagement :management="rfidManagement" @deleted-management="handleDeleted"
                                    @updated-management="handleUpdated" @deleted-management-error="handleDeleteManagementError" />
                            </div>
                        </TransitionGroup>

                        <Pagination :data="rfidManagementsPagination" @set-current-page="setCurrentPage" />
                    </div>
                    <div v-else>
                        <div class="bg-white p-7 mt-4 shadow-md rounded-lg">
                            No data yet.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>

<style>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
