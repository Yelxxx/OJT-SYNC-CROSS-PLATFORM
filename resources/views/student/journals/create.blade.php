<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('New Journal Entry') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('student.journals.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <x-input-label for="week" :value="__('OJT Week')" />
                            <x-text-input id="week" name="week" type="number" min="1" max="52"
                                :value="old('week', $journals->isEmpty() ? 1 : $journals->max('week') + 1)"
                                class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('week')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="title" :value="__('Title')" />
                            <x-text-input id="title" name="title" type="text"
                                :value="old('title')"
                                placeholder="e.g. Week 3: First Sprint" class="mt-1 block w-full" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="entry" :value="__('Entry')" />
                            <textarea id="entry" name="entry" rows="8" required
                                class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                placeholder="What did you learn this week? What tasks did you accomplish? Any challenges or reflections?">{{ old('entry') }}</textarea>
                            <x-input-error :messages="$errors->get('entry')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('student.journals.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 text-sm">
                                Submit Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
