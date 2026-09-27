<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            Company Applications
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Pending applications --}}
            <x-card flush>
                <div class="p-6">
                    <x-section-header title="Pending Review" subtitle="Companies waiting for approval" />

                    <div class="mt-4 space-y-4">
                        @forelse ($pendingCompanies as $company)
                            <div class="border border-primary-100 rounded-lg p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900">{{ $company->name }}</p>
                                        <p class="mt-0.5 text-sm text-gray-500">
                                            {{ $company->contact_person }}
                                            · {{ $company->contact_email }}
                                            @if ($company->phone) · {{ $company->phone }} @endif
                                        </p>
                                        <p class="text-sm text-gray-500">{{ $company->address }}</p>
                                        @if ($company->website)
                                            <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer"
                                               class="text-sm text-primary-600 hover:underline">{{ $company->website }}</a>
                                        @endif
                                        @if ($company->description)
                                            <p class="mt-2 text-sm text-gray-600 whitespace-pre-line">{{ $company->description }}</p>
                                        @endif
                                        <p class="mt-2 text-xs text-gray-400">Applied {{ $company->created_at?->diffForHumans() }}</p>
                                    </div>

                                    <div class="shrink-0 flex flex-col items-stretch gap-2">
                                        <form method="POST" action="{{ route('admin.companies.approve', $company) }}">
                                            @csrf
                                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent px-4 py-2 bg-primary-600 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                Approve
                                            </button>
                                        </form>
                                        <details class="w-full">
                                            <summary class="cursor-pointer text-center text-sm text-gray-500 hover:text-gray-700 select-none">Reject…</summary>
                                            <form method="POST" action="{{ route('admin.companies.reject', $company) }}" class="mt-2 space-y-2">
                                                @csrf
                                                <input type="text" name="reason" placeholder="Reason (optional)" maxlength="500"
                                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm" />
                                                <button type="submit" class="w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                                    Confirm rejection
                                                </button>
                                            </form>
                                        </details>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <x-empty-state icon="inbox" title="No pending applications"
                                message="When a company registers through the Partner with CvSU form, it will appear here for review." />
                        @endforelse
                    </div>
                </div>
            </x-card>

            {{-- Recently reviewed --}}
            <x-card flush>
                <div class="p-6">
                    <x-section-header title="Recently Reviewed" subtitle="Latest approved or rejected applications" />

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Company</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Contact</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Status</th>
                                    <th class="px-3 py-2 text-left font-medium text-gray-500">Reviewed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($reviewedCompanies as $company)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-900">{{ $company->name }}</td>
                                        <td class="px-3 py-2 text-gray-500">{{ $company->contact_person }} · {{ $company->contact_email }}</td>
                                        <td class="px-3 py-2">
                                            @if ($company->status === \App\Models\Company::STATUS_APPROVED)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Approved</span>
                                            @elseif ($company->status === \App\Models\Company::STATUS_REJECTED)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Rejected</span>
                                                @if ($company->rejection_reason)
                                                    <span class="block text-xs text-gray-400">{{ $company->rejection_reason }}</span>
                                                @endif
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gold-100 text-gold-800">Pending</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-gray-400">{{ $company->updated_at?->diffForHumans() }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">No reviewed applications yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>

        </div>
    </div>
</x-app-layout>
