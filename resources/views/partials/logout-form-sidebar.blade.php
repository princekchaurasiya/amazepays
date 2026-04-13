<li class="d-block rounded-lg">
    <form action="{{ route('userLogOut') }}" method="POST" class="mb-0">
        @csrf
        <button type="submit" class="btn btn-link text-dark text-left w-100 p-2 rounded-lg border-0"
            style="font-weight: inherit; text-decoration: none;">
            <i class="ti-power-off font-sm"></i><span> Logout</span>
        </button>
    </form>
</li>
