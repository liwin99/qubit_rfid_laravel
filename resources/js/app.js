import './bootstrap';

import { createApp } from 'vue'

import ClickOutside from './ClickOutsideDirective';
import RfidReaderManagement from './pages/RfidReaderManagement.vue';

// This element {"management-app"} only exists on 'rfid-management.index.blade.php'
let management = document.getElementById("management-app");

if (management) {
    // Setup vue on this element using component {"RfidReaderManagement"}
    createApp(RfidReaderManagement)
        .directive('click-outside', ClickOutside)
        .mount(management)
}
