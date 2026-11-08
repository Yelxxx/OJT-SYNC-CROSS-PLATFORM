<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Post a New Internship') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('hr.jobs.store') }}" class="space-y-5">
                        @csrf

                        <div>
                            <x-input-label for="title" :value="__('Position Title')" />
                            <x-text-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" required placeholder="e.g. Software Development Intern" />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <textarea id="description" name="description" rows="5" required
                                class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                placeholder="Describe the internship role and what the student will do...">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="requirements" :value="__('Requirements (one per line)')" />
                            <textarea id="requirements" name="requirements" rows="3"
                                class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                placeholder="e.g. Basic programming knowledge&#10;Good communication skills">{{ old('requirements') }}</textarea>
                            <x-input-error :messages="$errors->get('requirements')" class="mt-2" />
                        </div>

                        {{-- Target Academic Programs Selection --}}
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg space-y-3" x-data="{
                            openToAll: {{ old('eligible_programs') && in_array('ALL', old('eligible_programs', [])) ? 'true' : 'false' }},
                            toggleAll(e) {
                                this.openToAll = e.target.checked;
                            }
                        }">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-bold text-gray-900">Target College Programs / Courses</h4>
                                    <p class="text-xs text-gray-500">Only students from selected programs will see this opening in their feed.</p>
                                </div>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="checkbox" name="eligible_programs[]" value="ALL" @change="toggleAll($event)" :checked="openToAll"
                                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    <span class="text-xs font-bold text-primary-800">Open to ALL Programs</span>
                                </label>
                            </div>

                            <div x-show="!openToAll" class="space-y-3 pt-2">
                                @foreach (\App\Constants\AcademicProgram::grouped() as $college => $programs)
                                    @if ($college !== 'General / Universal')
                                        <div class="border-t border-gray-200 pt-2">
                                            <p class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-1.5">{{ $college }}</p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach ($programs as $code => $label)
                                                    <label class="inline-flex items-center gap-2 text-xs text-gray-700 hover:text-gray-900 cursor-pointer">
                                                        <input type="checkbox" name="eligible_programs[]" value="{{ $code }}"
                                                               @checked(is_array(old('eligible_programs')) && in_array($code, old('eligible_programs')))
                                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                                        <span>{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            <x-input-error :messages="$errors->get('eligible_programs')" class="mt-1" />
                        </div>

                        {{-- Core Competencies & Skills Gained --}}
                        <div>
                            <x-input-label for="skills_input" :value="__('Core Competencies & Skills Gained (comma-separated)')" />
                            <x-text-input id="skills_input" class="block mt-1 w-full text-sm" type="text" name="skills_input" :value="old('skills_input')"
                                          placeholder="e.g. Customer Relations, Opera PMS, Front Desk Operations, Complaint Resolution" />
                            <p class="text-xs text-gray-400 mt-1">These competencies will form the basis of the intern's verified skills credential upon completing their OJT.</p>
                            <x-input-error :messages="$errors->get('skills_input')" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="slots" :value="__('Number of Slots')" />
                                <x-text-input id="slots" class="block mt-1 w-full" type="number" name="slots" :value="old('slots', 1)" min="1" max="99" required />
                                <x-input-error :messages="$errors->get('slots')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                                    <option value="open" @selected(old('status', 'open') === 'open')>Open — accepting applications</option>
                                    <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                                </select>
                                <x-input-error :messages="$errors->get('status')" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <x-primary-button>
                                {{ __('Publish Job') }}
                            </x-primary-button>
                            <a href="{{ route('hr.jobs.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
