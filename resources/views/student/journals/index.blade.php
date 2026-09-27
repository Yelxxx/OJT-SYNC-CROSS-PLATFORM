<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('My E-Journals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between gap-4">
                <p class="text-sm text-gray-600">Document your weekly OJT learnings and reflections.</p>
                <x-button href="{{ route('student.journals.create') }}">
                    + New Entry
                </x-button>
            </div>

            {{-- Pending offline submissions --}}
            <div x-data x-show="$store.syncStatus.pending > 0" x-cloak
                class="rounded-md border border-gold-200 bg-gold-50 px-4 py-3 text-sm text-gold-700">
                <span x-text="$store.syncStatus.pending"></span>
                submission(s) saved on this device and waiting to sync — they'll appear here once uploaded.
            </div>

            @if ($journals->isEmpty())
                <x-card>
                    <x-empty-state icon="document-add" title="No journal entries yet" message="Document your weekly OJT learnings and reflections. Write your first entry!" />
                </x-card>
            @else
                <div class="space-y-4">
                    @foreach ($journals as $journal)
                        <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-3">
                                        <span class="shrink-0 inline-flex items-center justify-center h-10 w-10 rounded-full bg-gold-400 text-primary-900 font-bold text-sm">
                                            W{{ $journal->week }}
                                        </span>
                                        <div>
                                            <h3 class="font-semibold text-gray-900">{{ $journal->title }}</h3>
                                            <p class="text-xs text-gray-500">Submitted {{ $journal->submitted_at?->format('M d, Y g:i A') }}</p>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('student.journals.destroy', $journal) }}" onsubmit="return confirm('Delete this journal entry?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline">Delete</button>
                                    </form>
                                </div>
                                <p class="mt-4 text-gray-700 leading-relaxed whitespace-pre-line">{{ $journal->entry }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
