<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-primary-900">Partner with CvSU</h2>
        <p class="mt-1 text-sm text-gray-500">Register your company to post internships and hire student trainees</p>
    </div>

    @if (session('company_application_status') === 'pending')
        <div class="mb-6 rounded-lg border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800">
            <p class="font-semibold">Application submitted — thank you!</p>
            <p class="mt-1">
                Our OJT office will review your company details. Once approved, you will be able to
                sign in with the email and password you provided, post internship offers, and start
                receiving applications from CvSU students.
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('company.register.store') }}">
        @csrf

        <h3 class="text-sm font-semibold text-gray-900">Company details</h3>

        <!-- Company Name -->
        <div class="mt-3">
            <x-input-label for="company_name" :value="__('Company Name')" />
            <x-text-input id="company_name" class="block mt-1 w-full" type="text" name="company_name" :value="old('company_name')" required autofocus placeholder="e.g. Microsoft" />
            <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
        </div>

        <!-- Company Address -->
        <div class="mt-4">
            <x-input-label for="company_address" :value="__('Company Address')" />
            <x-text-input id="company_address" class="block mt-1 w-full" type="text" name="company_address" :value="old('company_address')" required placeholder="Street, City, Province" />
            <x-input-error :messages="$errors->get('company_address')" class="mt-2" />
        </div>

        <!-- Phone & Website -->
        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="company_phone" :value="__('Company Phone')" />
                <x-text-input id="company_phone" class="block mt-1 w-full" type="text" name="company_phone" :value="old('company_phone')" placeholder="(000) 000-1234" />
                <x-input-error :messages="$errors->get('company_phone')" class="mt-2" />
            </div>
            <div>
                <x-input-label for="company_website" :value="__('Website')" />
                <x-text-input id="company_website" class="block mt-1 w-full" type="text" name="company_website" :value="old('company_website')" placeholder="https://…" />
                <x-input-error :messages="$errors->get('company_website')" class="mt-2" />
            </div>
        </div>

        <!-- Company Description -->
        <div class="mt-4">
            <x-input-label for="company_description" :value="__('About the Company')" />
            <textarea id="company_description" name="company_description" rows="3" required
                class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                placeholder="What does your company do? What kind of interns are you looking for?">{{ old('company_description') }}</textarea>
            <x-input-error :messages="$errors->get('company_description')" class="mt-2" />
        </div>

        <h3 class="mt-6 text-sm font-semibold text-gray-900">HR account <span class="font-normal text-gray-500">(for signing in after approval)</span></h3>

        <!-- Contact Person -->
        <div class="mt-3">
            <x-input-label for="contact_person" :value="__('Contact Person')" />
            <x-text-input id="contact_person" class="block mt-1 w-full" type="text" name="contact_person" :value="old('contact_person')" required placeholder="HE personnel's full name" />
            <x-input-error :messages="$errors->get('contact_person')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <p class="mt-4 text-xs text-gray-500">
            Your account stays inactive until the CvSU OJT office approves your company.
            We will review your application as soon as possible.
        </p>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-primary-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="{{ route('register') }}">
                {{ __('I am a student') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Submit Application') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
