<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>
    <style>
        /* Your existing styles */
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
        .commits-info {
            font-size: 1rem;
            text-align: center;
            margin-top: 1rem;
            color: #333;
        }
        .commits-info span {
            display: block;
        }
        .loading {
            text-align: center;
            margin-top: 1rem;
        }
        .spinner {
            border: 4px solid rgba(0, 0, 0, 0.1);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border-left-color: #09f;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
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
                    <button
                        id="updateButton"
                        class="rounded-md px-3 py-2 text-black ring-1 ring-transparent transition hover:text-black/70 focus:outline-none focus-visible:ring-[#FF2D20] dark:text-white dark:hover:text-white/80 dark:focus-visible:ring-white"
                    >
                        Update Commit History
                    </button>
                </div>
                <div class="p-6">
                    <div id="loading" class="loading" style="display: none;">
                        <div class="spinner"></div>
                    </div>
                    <div id="commitDays" class="commits-info"></div>
                    <canvas id="commitsChart"></canvas>
                    <div id="commitMonths" class="commits-info"></div>
                    <p class="no-commits-message" id="noCommitsMessage" style="display: none;">No commits found in the last 3 months.</p>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    let commitsChart;

    document.getElementById('updateButton').addEventListener('click', function() {
        updateRepositoriesAndCommits();
    });

    document.getElementById('repository').addEventListener('change', function() {
        updateCommits(this.value);
    });

    function updateRepositoriesAndCommits() {
        document.getElementById('loading').style.display = 'block';
        fetch('/repositories')
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                const repositorySelect = document.getElementById('repository');
                repositorySelect.innerHTML = '<option value="" disabled selected>Select a repository</option>';
                data.forEach(repo => {
                    const option = document.createElement('option');
                    option.value = repo.id;
                    option.textContent = repo.name;
                    repositorySelect.appendChild(option);
                });
                if (data.length > 0) {
                    repositorySelect.value = data[0].id;
                    updateCommits(data[0].id);
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Error fetching repositories:', error);
                alert('Failed to fetch repositories. Please try again.');
            });
    }

    function updateCommits(repoId) {
        document.getElementById('loading').style.display = 'block';
        fetch(`/commits/${repoId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                if (commitsChart) {
                    commitsChart.destroy();
                }
                let ctx = document.getElementById('commitsChart').getContext('2d');
                const filteredLabels = data.labels.filter((_, index) => data.counts[index] > 0);
                const filteredCounts = data.counts.filter(count => count > 0);
                if (filteredCounts.length === 0) {
                    document.getElementById('noCommitsMessage').style.display = 'block';
                    document.getElementById('commitDays').innerText = '';
                    document.getElementById('commitMonths').innerText = '';
                    return;
                } else {
                    document.getElementById('noCommitsMessage').style.display = 'none';
                }
                commitsChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: filteredLabels,
                        datasets: [{
                            label: 'Commits',
                            data: filteredCounts,
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
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                console.error('Error fetching commits:', error);
                alert('Failed to fetch commits. Please try again.');
            });
    }
    </script>
    
</x-app-layout>
