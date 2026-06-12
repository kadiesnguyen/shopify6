import './bootstrap';
import './mobile-keyboard';
import './member-search-suggest';
import Alpine from 'alpinejs';
import { registerMemberChatComponents } from './member-chat';
import 'flowbite';

window.Alpine = Alpine;
registerMemberChatComponents(Alpine);
Alpine.start();
