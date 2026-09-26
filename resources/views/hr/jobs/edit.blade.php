<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Edit Job Posting') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('hr.jobs.update', $job) }}" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <div>
                            <x-input-label for="title" :value="__('Position Title')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title', $job->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="5" required
                                class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">{{ old('description', $job->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="requirements" :value="__('Requirements (one per line)')" />
                            <textarea id="requirements" name="requirements" rows="4"
                                class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">{{ old('requirements', $job->requirements) }}</textarea>
                            <x-input-error :messages="$errors->get('requirements')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="slots" :value="__('Number of Slots')" />
                                <x-text-input id="slots" class="block mt-1 w-full" type="number" name="slots" :value="old('slots', $job->slots)" min="1" max="99" required />
                                <x-input-error :messages="$errors->get('slots')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                                    <option value="open" @selected(old('status', $job->status) === 'open')>Open — accepting applications</option>
                                    <option value="closed" @selected(old('status', $job->status) === 'closed')>Closed</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <x-primary-button>
                                {{ __('Save Changes') }}
                            </x-primary-button>
                            <a href="{{ route('hr.jobs.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
