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
            color: #fff;
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

        #fetchMessage {
            color: #fff;
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
                    <select id="repositorySelect" name="repository_id">
                        <option value="" disabled selected>Select a repository</option>
                        @foreach($repositories as $repo)
                            <option value="{{ $repo->id }}">{{ $repo->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="p-6">
                    <div id="fetchMessage" class="commits-info" style="display: none;">
                        We are retrieving the commit history, this could take a little while.
                    </div>
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
        var commitsChartInstance = null;

        document.addEventListener('DOMContentLoaded', function() {
            var repositorySelect = document.getElementById('repositorySelect');
            var fetchMessage = document.getElementById('fetchMessage');
            var loading = document.getElementById('loading');
            var commitDays = document.getElementById('commitDays');
            var commitsChart = document.getElementById('commitsChart');
            var noCommitsMessage = document.getElementById('noCommitsMessage');

            repositorySelect.addEventListener('change', function() {
                var repositoryId = repositorySelect.value;
                if (!repositoryId) {
                    return;
                }

                fetchMessage.style.display = 'block';
                loading.style.display = 'block';
                commitDays.innerHTML = '';
                commitsChart.style.display = 'none';
                noCommitsMessage.style.display = 'none';

                var interval = setInterval(function() {
                    fetch(`/repositories/${repositoryId}/commits`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.message && data.message.includes('Please try again later')) {
                                console.log('Data is still being fetched...');
                                return;
                            }

                            clearInterval(interval);

                            if (data.length === 0) {
                                noCommitsMessage.style.display = 'block';
                                fetchMessage.style.display = 'none';
                                loading.style.display = 'none';
                                return;
                            }

                            var days = [];
                            var commitCounts = [];

                            data.forEach(item => {
                                days.push(item.date);
                                commitCounts.push(item.count);
                            });

                            commitDays.innerHTML = `Commits in the last 90 days: ${commitCounts.reduce((a, b) => a + b, 0)}`;

                            if (commitsChartInstance) {
                                commitsChartInstance.destroy();
                            }

                            commitsChart.style.display = 'block';
                            commitsChartInstance = new Chart(commitsChart, {
                                type: 'bar',
                                data: {
                                    labels: days,
                                    datasets: [{
                                        label: 'Number of Commits',
                                        data: commitCounts,
                                        backgroundColor: '#3490dc',
                                        borderColor: '#3490dc',
                                        borderWidth: 1
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: {
                                        legend: {
                                            display: false
                                        }
                                    },
                                    scales: {
                                        x: {
                                            title: {
                                                display: true,
                                                text: 'Days'
                                            }
                                        },
                                        y: {
                                            title: {
                                                display: true,
                                                text: 'Number of Commits'
                                            },
                                            beginAtZero: true
                                        }
                                    }
                                }
                            });

                            fetchMessage.style.display = 'none';
                            loading.style.display = 'none';
                        })
                        .catch(error => {
                            console.error('Error fetching data:', error);
                            fetchMessage.style.display = 'none';
                            loading.style.display = 'none';
                            clearInterval(interval);
                        });
                }, 3000);
            });
        });
    </script>
</x-app-layout>
