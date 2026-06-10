import './bootstrap';
import './member-chat';
import './admin-chat';
import Alpine from 'alpinejs';
import 'flowbite';
import Swal from 'sweetalert2';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Swal = Swal;
window.Chart = Chart;

Alpine.start();
