<x-layouts.guest>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="vstack gap-3">
        @csrf
        <div>
            <label class="form-label" for="email">Email</label>
            <input class="form-control @error('email') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-primary" type="submit">Wyslij link resetujacy</button>
    </form>

    <p class="text-center mt-4 mb-0">
        <a href="{{ route('login') }}">Powrot do logowania</a>
    </p>
</x-layouts.guest>
