<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/index.css') }}">
    <title>Регистрация - VityaNails</title>
</head>
<body>
    <form action="{{ route('register.submit') }}" method="post">
        @csrf
        <div class="form-header">
            <h1>VityaNails</h1>
            <p class="subtitle">Студия маникюра</p>
        </div>
        <h2>Регистрация</h2>
        <div class="input-group">
            <label>Фамилия</label>
            <input type="text" name="lastname" placeholder="Введите свою фамилию" required title="Фамилия должна содержать только буквы">
        </div>
        <div class="input-group">
            <label>Имя</label>
            <input type="text" name="name" placeholder="Введите свое имя" required title="Имя должно содержать только буквы">
        </div>
        <div class="input-group">
            <label>Отчество</label>
            <input type="text" name="firstname" placeholder="Введите свое отчество" title="Отчество должно содержать только буквы">
        </div>
        <div class="input-group">
            <label for="phoneInput">Телефон</label>
            <input type="tel" name="nomber_tel" id="phoneInput" placeholder="+7 (___) ___-__-__" required>
        </div>
        <div class="input-group">
            <label>Пароль</label>
            <input type="password" name="password" placeholder="Введите пароль (минимум 6 символов)" required minlength="6">
        </div>
        <button type="submit">Зарегистрироваться</button>
        <p class="auth-link">
            У вас уже есть аккаунт? <a href="{{ route('login') }}">Войти</a>
        </p>
        @if(session('message'))
            <p class="msg">{{ session('message') }}</p>
        @endif
        @if($errors->any())
            <p class="msg">{{ $errors->first() }}</p>
        @endif
    </form>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phoneInput');
        phoneInput.addEventListener('input', function() {
            let input = this.value.replace(/[^0-9]/g, '');
            if (input.length > 11) input = input.slice(0, 11);
            let formattedNumber = '+7 ';
            if (input.length > 1) formattedNumber += '(' + input.slice(1, 4);
            if (input.length >= 4) formattedNumber += ') ' + input.slice(4, 7);
            if (input.length >= 7) formattedNumber += '-' + input.slice(7, 9);
            if (input.length >= 9) formattedNumber += '-' + input.slice(9, 11);
            this.value = formattedNumber.trim();
        });
    });
</script>
</html>
