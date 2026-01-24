// forms/register.js

import { handleForm } from './handleForm.js';

export function initRegisterForm(csrf) {
    handleForm('registerForm', '/api/auth/register', {
        validate: d => {
            const e = {};
            if (!d.login?.trim()) e.login = 'Введите логин';
            else if (d.login.length < 3) e.login = 'Минимум 3 символа';
            if (!d.email?.trim()) e.email = 'Введите email';
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(d.email)) e.email = 'Некорректный email';
            if (!d.password) e.password = 'Введите пароль';
            else if (d.password.length < 8) e.password = 'Минимум 8 символов';
            if (d.password !== d.password_confirm) e.password_confirm = 'Пароли не совпадают';
            if (!d.username?.trim()) e.username = 'Введите имя';
            return e;
        }
    }, csrf);
}