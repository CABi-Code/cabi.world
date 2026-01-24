// forms/modpack-apply.js

import { handleForm, showErrors, showAlert } from './handleForm.js';

export function initModpackApply(csrf) {
    handleForm('profileForm', '/api/user/update', {}, csrf);
    handleForm('contactsForm', '/api/user/update', {}, csrf);
    handleForm('applicationForm', '/api/modpack/apply', {
        validate: d => {
            const e = {};
            if (!d.message?.trim()) e.message = 'Введите сообщение';
            if (!d.discord && !d.telegram && !d.vk) e.discord = 'Укажите контакт';
            return e;
        },
        onSuccess: () => setTimeout(() => location.reload(), 500)
    }, csrf);
    handleForm('editAppForm', '/api/application/update', { onSuccess: () => location.reload() }, csrf);
    handleForm('editMyAppForm', '/api/application/update', { onSuccess: () => location.reload() }, csrf);
}