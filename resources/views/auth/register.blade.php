<x-layouts.guest>
    <form method="POST" action="{{ route('register.store') }}" class="vstack gap-3">
        @csrf
        <div>
            <label class="form-label" for="name">Nazwa uzytkownika</label>
            <input class="form-control @error('name') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required>
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

        <div>
            <label class="form-label" for="password_confirmation">Powtorz haslo</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button class="btn btn-primary" type="submit">Zarejestruj</button>
    </form>

    <p class="text-center mt-4 mb-0">
        Masz juz konto?
        <a href="{{ route('login') }}">Zaloguj sie</a>
    </p>
</x-layouts.guest>

