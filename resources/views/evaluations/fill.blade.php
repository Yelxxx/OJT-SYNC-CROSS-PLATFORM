<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold text-primary-900">Student Evaluation</h2>
        <p class="mt-1 text-sm text-gray-600">
            {{ $evaluation->student->user->name }} — {{ $evaluation->jobPosting?->title ?? 'OJT Intern' }}
        </p>
        <p class="text-xs text-gray-400">{{ $evaluation->jobPosting?->company->name ?? '' }}</p>
    </div>

    <form method="POST" action="{{ route('evaluations.store', $evaluation->token) }}" class="space-y-6">
        @csrf

        @foreach ([
            'punctuality' => 'Punctuality',
            'quality_of_work' => 'Quality of Work',
            'initiative' => 'Initiative',
            'communication' => 'Communication',
        ] as $key => $label)
            <div>
                <x-input-label :value="__($label)" />
                <div class="mt-2 flex gap-3">
                    @for ($i = 1; $i <= 5; $i++)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="{{ $key }}" value="{{ $i }}" required class="sr-only peer" {{ old($key) == $i ? 'checked' : '' }} />
                            <span class="block text-center py-2.5 rounded-lg border border-primary-200 bg-white text-sm font-medium text-gray-700 peer-checked:bg-primary-600 peer-checked:text-white peer-checked:border-primary-600 hover:border-primary-400 transition">
                                {{ $i }}
                            </span>
                        </label>
                    @endfor
                </div>
                <div class="mt-1 flex justify-between text-[11px] text-gray-400">
                    <span>Poor</span>
                    <span>Excellent</span>
                </div>
                <x-input-error :messages="$errors->get($key)" class="mt-2" />
            </div>
        @endforeach

        <div>
            <x-input-label for="comments" :value="__('Comments (optional)')" />
            <textarea id="comments" name="comments" rows="4" class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm" placeholder="Any feedback for the student?">{{ old('comments') }}</textarea>
            <x-input-error :messages="$errors->get('comments')" class="mt-2" />
        </div>

        <div>
            <x-primary-button class="w-full justify-center">
                {{ __('Submit Evaluation') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
