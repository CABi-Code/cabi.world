// js/app.js

import { initMobileNav } from './mobile-nav.js';
import { initTheme } from './theme.js';
import { initPasswordToggle } from './password-toggle.js';
import { initViewMode } from './view-mode.js';
import { initFeedSort } from './feed-sort.js';
import { initNotifications } from './notifications.js';
import { initAdvancedImageUpload } from './image-editor/index.js';
import { initModals } from './modals.js';
import { initLightbox } from './lightbox.js';
import { initLoginForm } from './forms/login.js';
import { initRegisterForm } from './forms/register.js';
import { initModpackApply } from './forms/modpack-apply.js';
import { initSaveColors } from './initSaveColors.js';

document.addEventListener('DOMContentLoaded', () => {
    // csrf-токен нужен почти всем сетевым запросам
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    // Инициализация модулей
    initMobileNav();
    initTheme();
    initPasswordToggle();
    initViewMode();
    initFeedSort();
    initNotifications(csrf);
    initAdvancedImageUpload(csrf);
    initModals();
    initLightbox();

    initLoginForm(csrf);
    initRegisterForm(csrf);
    initModpackApply(csrf);

    // Сохранение цветов (градиенты баннера и аватара)
    initSaveColors(csrf);
});
