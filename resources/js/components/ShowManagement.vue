<script>
import SelectDropdown from './../components/SelectDropdown.vue'

export default {
    emits: [
        'deletedManagement',
        'updatedManagement',
    ],
    props: [
        'management'
    ],
    components: {
        SelectDropdown,
    },
    data() {
        return {
            id: null,
            location_1_id: null,
            location_1_name: null,
            location_2_id: null,
            location_2_name: null,
            location_3_id: null,
            location_3_name: null,
            location_4_id: null,
            location_4_name: null,
            name: null,
            project_id: null,
            project_name: null,
            used_for_attendance: false,
            isEditing: false,
            errors: {
                name: '',
                project_name: '',
                location_1_id: '',
                location_2_id: '',
                location_3_id: '',
                location_4_id: '',
            },
        };
    },
    created() {
        this.id = this.management.id
        this.location_1_id = this.management.location_1_id
        this.location_1_name = this.management.location_one.name
        this.location_2_id = this.management.location_2_id
        this.location_2_name = this.management.location_two.name
        this.location_3_id = this.management.location_3_id
        this.location_3_name = !!this.management.location_three ? this.management.location_three.name : '-'
        this.location_4_id = this.management.location_4_id
        this.location_4_name = !!this.management.location_four ? this.management.location_four.name : '-'
        this.name = this.management.name
        this.project_id = this.management.project_id
        this.project_name = this.management.project.name
        this.used_for_attendance = !!this.management.used_for_attendance
        this.old_data = this.management;
    },
    methods: {
        async deleteManagement() {
            if (confirm('Are you sure you want to delete?')) {
                try {
                    const res = await axios.delete(`/rfid-management/${this.id}`);

                    this.$emit('deletedManagement')
                } catch (error) {
                    this.$emit('deletedManagementError', error.response.data.message)
                }
            }
        },
        async updateManagement(e) {
            let formData = new FormData(e.target);

            const data = Object.fromEntries([...formData.entries()].map(([key, value]) => [key, value === "" ? null : value]));

            data.used_for_attendance = data.used_for_attendance == 'on' ? true : false;

            let validForm = this.validateForm(data);

            if (!validForm) {
                return;
            }

            try {
                const res = await axios.put(`/rfid-management/${this.id}`, data);

                this.resetErrors();
                this.toggleIsEditing();

                this.$emit("updatedManagement");

                this.location_1_id = data.location_1_id
                this.location_1_name = data.location_1_name
                this.location_2_id = data.location_2_id
                this.location_2_name = data.location_2_name
                this.location_3_id = data.location_3_id
                this.location_3_name = data.location_3_name
                this.location_4_id = data.location_4_id
                this.location_4_name = data.location_4_name
                this.name = data.name
                this.project_id = data.project_id
                this.project_name = data.project_name
                this.used_for_attendance = !!data.used_for_attendance
                this.old_data = data

            } catch (errors) {
                if (errors.response) {
                    console.log('errors', errors.response)
                    if (errors.response.data.errors.name) {
                        this.errors.name = errors.response.data.errors.name[0]
                    }
                    if (errors.response.data.errors.project_id) {
                        this.errors.project_id = errors.response.data.errors.project_id[0]
                    }
                    if (errors.response.data.errors.location_1_id) {
                        this.errors.location_1_id = errors.response.data.errors.location_1_id[0]
                    }
                } else {
                    console.log(errors);
                }
            }
        },
        validateForm(data) {
            let isValid = true;

            // Reset errors
            this.resetErrors();

            if (!data.name) {
                this.errors.name = 'Reader is required.';
                isValid = false;
            }

            if (!data.project_id) {
                this.errors.project_name = 'Project is required.';
                isValid = false;
            }

            if (!data.project_id) {
                this.errors.location_1_id = 'Location 1 is required.';
                isValid = false;
            }

            if (!data.location_2_id) {
                this.errors.location_2_id = 'Location 2 is required.';
                isValid = false;
            }

            if (!!data.location_4_id && !data.location_3_id) {
                this.errors.location_3_id = 'Location 3 is required if location 4 is set.';
                isValid = false;
            }

            const uniqueValues = new Set();

            for (let i = 1; i <= 4; i++) {
                const locationId = `location_${i}_id`;
                const value = data[locationId];

                // Validate uniqueness only if the field is not empty
                if (value !== null) {
                    if (uniqueValues.has(value)) {
                        this.errors[locationId] = `Locations must be unique.`;
                        isValid = false;
                    } else {
                        uniqueValues.add(value);
                    }
                }
            }

            return isValid;
        },
        resetErrors() {
            Object.keys(this.errors).forEach(field => {
                this.errors[field] = '';
            });
        },
        cancel() {
            this.isEditing = false;
            this.location_1_name = !!this.old_data.location_one ? this.old_data.location_one.name : this.old_data.location_1_name
            this.location_2_name = !!this.old_data.location_two ? this.old_data.location_two.name : this.old_data.location_2_name
            this.location_3_name = !!this.old_data.location_three ?
                this.old_data.location_three.name : !!this.old_data.location_3_name ? this.old_data.location_3_name : '-'
            this.location_4_name = !!this.old_data.location_four ?
                this.old_data.location_four.name : !!this.old_data.location_4_name ? this.old_data.location_4_name :'-'
            this.name = this.old_data.name
            this.project_name = this.old_data.project_name
            this.used_for_attendance = !!this.old_data.used_for_attendance
        },
        toggleIsEditing() {
            this.isEditing = !this.isEditing
            this.resetErrors();
        },
    },
}
</script>

<template>
    <Transition name="slide-fade">
        <div class="bg-white p-4 rounded-lg" v-if="!isEditing">
            <div class="flex gap-3 justify-between flex-wrap mb-3">
                <div class="flex-grow">
                    <label class="text-sm">Reader</label>
                    <div class="border rounded-md bg-gray-50 p-2 px-3 text-gray-600">{{ name }}</div>
                </div>
                <div class="flex-grow">
                    <label class="text-sm">Project</label>
                    <div class="border rounded-md bg-gray-50 p-2 px-3 text-gray-600">{{ project_name }}</div>
                </div>
                <div class="flex-grow">
                    <label class="text-sm">Location 1</label>
                    <div class="border rounded-md bg-gray-50 p-2 px-3 text-gray-600">{{ location_1_name }}</div>
                </div>
                <div class="flex-grow">
                    <label class="text-sm">Location 2</label>
                    <div class="border rounded-md bg-gray-50 p-2 px-3 text-gray-600">{{ location_2_name ?? '-' }}
                    </div>
                </div>
                <div class="flex-grow">
                    <label class="text-sm">Location 3</label>
                    <div class="border rounded-md bg-gray-50 p-2 px-3 text-gray-600">{{ location_3_name ?? '-' }}
                    </div>
                </div>
                <div class="flex-grow">
                    <label class="text-sm">Location 4</label>
                    <div class="border rounded-md bg-gray-50 p-2 px-3 text-gray-600">{{ location_4_name ?? '-' }}
                    </div>
                </div>
            </div>
            <div class="mt-3 mb-5 flex items-center">
                <input id="used_for_attendance" type="checkbox" disabled v-model="used_for_attendance" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                <label for="used_for_attendance" class="ms-2 text-sm text-gray-800">Used For Attendance</label>
            </div>

            <div class="text-left">
                <button type="button"
                    class="text-white mr-1 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center"
                    @click="toggleIsEditing">
                    Edit
                </button>
                <button type="button"
                    class="inline-flex items-center justify-center bg-red-600 border border-transparent rounded-lg px-5 py-2.5 font-normal text-sm text-white tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    @click="deleteManagement">
                    Delete
                </button>
            </div>
        </div>
        <div v-else>
            <form @submit.prevent="updateManagement($event)" id="edit-form"
                class="bg-white shadow-2xl border-gray-300 border-2 rounded-lg p-5 mt-4 transition duration-300 ease-in-out">
                <div class="flex flex-wrap gap-2 flex-grow">
                    <div class="w-full md:w-1/3 md:flex-1">
                        <label for="name">Reader</label>
                        <input type="text" name="name" id="name" v-model="name"
                            class="border border-gray-300 text-gray-900 rounded-lg focus:ring-blue-500 w-full focus:border-blue-500 block p-3"
                            placeholder="Reader...">
                        <div v-if="errors.name" class="text-xs" style="color: red;">{{ errors.name }}</div>
                    </div>
                    <div class="w-full md:w-1/3 md:flex-1">
                        <label for="project_name">Project</label>
                        <SelectDropdown label_name="Project" select_name="project" data_url="/ajax/get-projects"
                            :selected_name="project_name" :selected_id="project_id" />
                        <div v-if="errors.project_name" class="text-xs" style="color: red;">{{ errors.project_name }}</div>
                    </div>
                    <div class="w-full md:flex md:flex-wrap md:gap-2 md:flex-grow md:mt-4">
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_1_id">Location 1</label>
                            <SelectDropdown label_name="Location 1" select_name="location_1"
                                :selected_name="location_1_name" :selected_id="location_1_id"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_1_id" class="text-xs" style="color: red;">{{ errors.location_1_id
                            }}</div>
                        </div>
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_2_id">Location 2</label>
                            <SelectDropdown label_name="Location 2" select_name="location_2"
                                :selected_name="location_2_name" :selected_id="location_2_id"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_2_id" class="text-xs" style="color: red;">{{ errors.location_2_id
                            }}</div>
                        </div>
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_3_name">Location 3</label>
                            <SelectDropdown label_name="Location 3" select_name="location_3"
                                :selected_name="location_3_name" :selected_id="location_3_id"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_3_id" class="text-xs" style="color: red;">{{ errors.location_3_id
                            }}</div>
                        </div>
                        <div class="mb-3 md:w-1/3 md:flex-1">
                            <label for="location_4_name">Location 4</label>
                            <SelectDropdown label_name="Location 4" select_name="location_4"
                                :selected_name="location_4_name" :selected_id="location_4_id"
                                data_url="/ajax/get-locations" />
                            <div v-if="errors.location_4_id" class="text-xs" style="color: red;">{{ errors.location_4_id
                            }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 mb-5 flex items-center">
                    <input id="used_for_attendance" name="used_for_attendance" type="checkbox" v-model="used_for_attendance" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    <label for="used_for_attendance" class="ms-2 text-gray-900">Used For Attendance</label>
                </div>
                <button type="submit"
                    class="mr-1 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Save
                </button>
                <button type="button" @click="cancel"
                    class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Cancel
                </button>
            </form>
        </div>
    </Transition>
</template>

<style>
.slide-fade-enter-active {
    transition: all 0.2s ease-out;
}

.slide-fade-leave-active {
    transition: all 0.2s cubic-bezier(1, 0.5, 0.8, 1);
}

.slide-fade-enter-from,
.slide-fade-leave-to {
    transform: translateX(20px);
    opacity: 0;
}
</style>
