<x-layouts.guest>
    <form method="POST" action="{{ route('login.store') }}" class="vstack gap-3">
        @csrf
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="form-label" for="password">Haslo</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
            <label class="form-check-label" for="remember">Zapamietaj mnie</label>
        </div>

        <button class="btn btn-primary" type="submit">Zaloguj</button>
    </form>

    <p class="text-center mt-4 mb-0">
        Nie masz konta?
        <a href="{{ route('register') }}">Zarejestruj sie</a>
    </p>
</x-layouts.guest>

