<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-primary-900">Create your account.</h2>
        <p class="mt-1 text-sm text-gray-500">Register as a student to begin your OJT journey.</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Student Number -->
        <div class="mt-4">
            <x-input-label for="student_no" :value="__('Student Number')" />
            <x-text-input id="student_no" class="block mt-1 w-full" type="text" name="student_no" :value="old('student_no')" required placeholder="e.g. 2024-1001" />
            <x-input-error :messages="$errors->get('student_no')" class="mt-2" />
        </div>

        <!-- Program & Year -->
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="program" :value="__('Course / Program')" />
                <select id="program" name="program" required
                        class="mt-1 block w-full text-sm border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                    <option value="">Select your course...</option>
                    @foreach (\App\Constants\AcademicProgram::grouped() as $college => $programs)
                        @if ($college !== 'General / Universal')
                            <optgroup label="{{ $college }}">
                                @foreach ($programs as $code => $label)
                                    <option value="{{ $code }}" @selected(old('program') === $code)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('program')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="year_level" :value="__('Year Level')" />
                <select id="year_level" name="year_level"
                        class="mt-1 block w-full text-sm border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                    <option value="">Select year level...</option>
                    <option value="3rd Year" @selected(old('year_level') === '3rd Year')>3rd Year</option>
                    <option value="4th Year" @selected(old('year_level') === '4th Year')>4th Year</option>
                    <option value="Graduating" @selected(old('year_level') === 'Graduating')>Graduating</option>
                </select>
                <x-input-error :messages="$errors->get('year_level')" class="mt-2" />
            </div>
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-primary-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="{{ route('company.register') }}">
                {{ __('Registering as a company?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
