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

        <div>
            <label class="form-label" for="secret_tag">Secret TAG</label>
            <input class="form-control @error('secret_tag') is-invalid @enderror" type="text" id="secret_tag" name="secret_tag" value="{{ old('secret_tag') }}" required>
            @error('secret_tag')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-primary" type="submit">Zarejestruj</button>
    </form>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-body">
            <h2 class="h6 mb-2">Secret TAG</h2>
            <p class="mb-0 small">
                Aby pobrac dane zwierzaka z hodowli (zbierane od jego wyklucia), potrzebny bedzie
                <strong>SECRET TAG</strong>. Jest to kod (min. 5 znakow), ktory znajduje sie na
                certyfikacie hodowlanym.
            </p>
        </div>
    </div>

    <p class="text-center mt-4 mb-0">
        Masz juz konto?
        <a href="{{ route('login') }}">Zaloguj sie</a>
    </p>
</x-layouts.guest>
