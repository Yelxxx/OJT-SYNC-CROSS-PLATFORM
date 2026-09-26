<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            Company Profile
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('hr.company.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- Logo --}}
                    <div class="flex items-center gap-4">
                        @if ($company->logo_path)
                            <img src="{{ asset('storage/'.$company->logo_path) }}" alt="{{ $company->name }} logo"
                                 class="h-16 w-16 rounded-lg object-contain border border-primary-100 bg-white p-1">
                        @else
                            <div class="h-16 w-16 rounded-lg border border-dashed border-primary-300 flex items-center justify-center text-primary-300 text-xl font-bold">
                                {{ mb_substr($company->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <x-input-label for="logo" :value="__('Company logo (PNG, JPG, SVG — max 1MB)')" />
                            <input type="file" id="logo" name="logo"
                                   class="mt-1 block text-sm text-gray-500 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100">
                            @error('logo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Company name')" />
                        <x-text-input id="name" name="name" type="text" :value="old('name', $company->name)" required class="mt-1 block w-full" />
                        @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-input-label for="address" :value="__('Address')" />
                        <x-text-input id="address" name="address" type="text" :value="old('address', $company->address)" class="mt-1 block w-full" />
                        @error('address') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="contact_email" :value="__('Contact email')" />
                            <x-text-input id="contact_email" name="contact_email" type="email" :value="old('contact_email', $company->contact_email)" class="mt-1 block w-full" />
                            @error('contact_email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <x-input-label for="phone" :value="__('Phone')" />
                            <x-text-input id="phone" name="phone" type="text" :value="old('phone', $company->phone)" class="mt-1 block w-full" />
                            @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <x-input-label for="website" :value="__('Website')" />
                        <x-text-input id="website" name="website" type="url" placeholder="https://example.com" :value="old('website', $company->website)" class="mt-1 block w-full" />
                        @error('website') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <x-input-label for="description" :value="__('About the company')" />
                        <textarea id="description" name="description" rows="4"
                                  class="mt-1 block w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">{{ old('description', $company->description) }}</textarea>
                        @error('description') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                                class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                            Save changes
                        </button>
                        <a href="{{ route('hr.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
