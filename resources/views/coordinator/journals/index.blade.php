<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Student E-Journals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Search --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4">
                <form method="GET" action="{{ route('coordinator.journals.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Search student')" />
                        <x-text-input id="q" name="q" type="text" value="{{ $q }}" placeholder="e.g. Peter Parker" class="mt-1 block w-full" />
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                        Search
                    </button>
                    @if ($q)
                        <a href="{{ route('coordinator.journals.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Journals --}}
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                <div class="p-6 space-y-6">
                    @forelse ($journals as $journal)
                        <div class="border border-primary-100 rounded-lg p-5 hover:border-primary-200 transition">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="shrink-0 inline-flex items-center justify-center h-9 w-9 rounded-full bg-gold-400 text-primary-900 font-bold text-xs">
                                        W{{ $journal->week }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900 truncate">{{ $journal->title }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $journal->student->user->name }} · {{ $journal->student->student_no }} · {{ $journal->submitted_at?->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 text-sm text-gray-700 leading-relaxed whitespace-pre-line line-clamp-4">{{ $journal->entry }}</p>
                        </div>
                    @empty
                        <x-empty-state icon="document" title="No journal entries found" message="Journals submitted by students will appear here." />
                    @endforelse
                </div>
            </div>

            <div class="mt-6">
                {{ $journals->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
