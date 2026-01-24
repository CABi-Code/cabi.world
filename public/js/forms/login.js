// forms/login.js

import { handleForm, showErrors, showAlert } from './handleForm.js';

export function initLoginForm(csrf) {
    handleForm('loginForm', '/api/auth/login', {
        validate: d => {
            const e = {};
            if (!d.login?.trim()) e.login = 'Введите логин';
            if (!d.password) e.password = 'Введите пароль';
            return e;
        }
    }, csrf);
}