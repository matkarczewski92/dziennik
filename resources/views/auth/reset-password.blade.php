<x-layouts.guest>
    <form method="POST" action="{{ route('password.update') }}" class="vstack gap-3">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="form-label" for="password">Nowe haslo</label>
            <input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" required>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div>
            <label class="form-label" for="password_confirmation">Powtorz nowe haslo</label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button class="btn btn-primary" type="submit">Zmien haslo</button>
    </form>
</x-layouts.guest>
