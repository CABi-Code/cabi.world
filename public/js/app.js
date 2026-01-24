// js/app.js

import { esc, getCookie, setCookie } 			from './utils/common.js';

import { initMobileNav }						from './mobile-nav.js';
import { initTheme }							from './theme.js';
import { initPasswordToggle }					from './password-toggle.js';
import { initViewMode }							from './view-mode.js';
import { initFeedSort }							from './feed-sort.js';
import { initNotifications }					from './notifications.js';
import { initImageUpload }						from './image-upload.js';
import { initModals }							from './modals.js';
import { initLightbox }							from './lightbox.js';

import { handleForm, showErrors, showAlert } 	from './forms/handleForm.js';
import { initLoginForm }                        from './forms/login.js';
import { initRegisterForm }                     from './forms/register.js';
import { initModpackApply }                     from './forms/modpack-apply.js';

import { initSaveColors }						from './initSaveColors.js';



document.addEventListener('DOMContentLoaded', () => {
    // csrf-токен нужен почти всем сетевым запросам
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
	
    // Порядок инициализации обычно не критичен, но логично так:
    initMobileNav();
    initTheme();
    initPasswordToggle();
    initViewMode();
    initFeedSort();
    initNotifications(csrf);
    initImageUpload(csrf);
    initModals();
    initLightbox();
	
    initLoginForm(csrf);
    initRegisterForm(csrf);
    initModpackApply(csrf);

    // Сохранение цветов (градиенты баннера и аватара)
    initSaveColors(csrf);
});