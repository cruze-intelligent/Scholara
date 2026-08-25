<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Gate Passes') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('gate-passes.create') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + Request a gate pass
                </a>
            </div>

            <x-card>
                @forelse ($gatePasses as $gatePass)
                    <div class="border-b border-gray-100 py-3 last:border-0">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-medium">{{ $gatePass->student->full_name ?? 'Unknown student' }}</p>
                                <p class="text-sm text-gray-500 mt-1">{{ $gatePass->reason }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Requested by {{ $gatePass->requestedBy->name }}
                                    @if ($gatePass->approvedBy) &middot; decided by {{ $gatePass->approvedBy->name }} @endif
                                    @if ($gatePass->departed_at) &middot; departed {{ $gatePass->departed_at->format('d M, H:i') }} @endif
                                    @if ($gatePass->returned_at) &middot; returned {{ $gatePass->returned_at->format('d M, H:i') }} @endif
                                </p>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <x-badge :color="match($gatePass->status) { 'approved' => 'green', 'rejected' => 'red', default => 'yellow' }">
                                    {{ ucfirst($gatePass->status) }}
                                </x-badge>

                                @if (auth()->user()->hasAnyRole(['admin', 'teacher']))
                                    @if ($gatePass->status === 'pending')
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('gate-passes.approve', $gatePass) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="approved">
                                                <button class="text-xs font-medium text-green-600 hover:text-green-800">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('gate-passes.approve', $gatePass) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="rejected">
                                                <button class="text-xs font-medium text-red-500 hover:text-red-700">Reject</button>
                                            </form>
                                        </div>
                                    @elseif ($gatePass->status === 'approved' && ! $gatePass->departed_at)
                                        <form method="POST" action="{{ route('gate-passes.depart', $gatePass) }}">
                                            @csrf @method('PATCH')
                                            <button class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Log departure</button>
                                        </form>
                                    @elseif ($gatePass->departed_at && ! $gatePass->returned_at)
                                        <form method="POST" action="{{ route('gate-passes.return', $gatePass) }}">
                                            @csrf @method('PATCH')
                                            <button class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Log return</button>
                                        </form>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No gate passes yet." />
                @endforelse
            </x-card>

            {{ $gatePasses->links() }}
        </div>
    </div>
</x-app-layout>
