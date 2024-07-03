<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <style>
        
        select {
            border: 1px solid #ccc;
            border-radius: 4px;
            color: #333; 
            background-color: #fff;
            appearance: none;
            -webkit-appearance: none; 
        }

        select option {
            color: #333; 
            background-color: #fff; 
        }

        .no-commits-message {
            font-size: 1.2rem;
            text-align: center;
            margin-top: 1rem;
            color: #777;
        }
    </style>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    {{ __("Commit History") }}
                </div>
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <label for="repository">Select Repository:</label>
                    <select id="repository" name="repository">
                        <option value="" disabled selected>Select a repository</option>
                        @foreach($repositories as $repository)
                            <option value="{{ $repository->id }}">{{ $repository->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="p-6">
                    <canvas id="commitsChart"></canvas>
                    <p class="no-commits-message" id="noCommitsMessage" style="display: none;">No commits found in the last 3 months.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let commitsChart;

        document.getElementById('repository').addEventListener('change', function() {
            let repoId = this.value;
            fetch(`/api/commits/${repoId}`)
                .then(response => response.json())
                .then(data => {
                    if (commitsChart) {
                        commitsChart.destroy();
                    }

                    let ctx = document.getElementById('commitsChart').getContext('2d');

                    if (data.counts.length === 0 || data.counts.every(count => count === 0)) {
                        document.getElementById('noCommitsMessage').style.display = 'block';
                        return;
                    } else {
                        document.getElementById('noCommitsMessage').style.display = 'none';
                    }

                    commitsChart = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'Commits',
                                data: data.counts,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
        });
    </script>
</x-app-layout>
