/**
 * L4D2 Player Stats - Frontend JavaScript
 * DataTables 初始化、Chart.js 圖表、AJAX 搜尋
 */
jQuery(document).ready(function ($) {

    // ============================================================
    // DataTables 初始化
    // ============================================================
    var dtDefaults = {
        pageLength: 25,
        responsive: true,
        language: {
            search: '搜尋:',
            lengthMenu: '顯示 _MENU_ 筆',
            info: '第 _START_ 至 _END_ 筆，共 _TOTAL_ 筆',
            infoEmpty: '無資料',
            infoFiltered: '(從 _MAX_ 筆中篩選)',
            paginate: {
                first: '首頁',
                last: '末頁',
                next: '下一頁',
                previous: '上一頁',
            },
            zeroRecords: '找不到符合的資料',
        },
    };

    // 排行榜
    if ($('#l4d2-leaderboard').length) {
        $('#l4d2-leaderboard').DataTable(
            $.extend({}, dtDefaults, {
                order: [[2, 'desc']],
            })
        );
    }

    // 武器統計
    if ($('#l4d2-weapons-table').length) {
        $('#l4d2-weapons-table').DataTable(
            $.extend({}, dtDefaults, {
                order: [[3, 'desc']],
            })
        );
    }

    // 場次記錄
    if ($('#l4d2-sessions-table').length) {
        $('#l4d2-sessions-table').DataTable(
            $.extend({}, dtDefaults, {
                order: [[0, 'desc']],
                pageLength: 20,
            })
        );
    }

    // 場次詳細 - 玩家表現
    if ($('#l4d2-session-players-table').length) {
        $('#l4d2-session-players-table').DataTable(
            $.extend({}, dtDefaults, {
                order: [[1, 'desc']],
                paging: false,
                searching: false,
                info: false,
            })
        );
    }

    // 場次詳細 - 武器明細
    if ($('#l4d2-session-weapons-table').length) {
        $('#l4d2-session-weapons-table').DataTable(
            $.extend({}, dtDefaults, {
                order: [[3, 'desc']],
            })
        );
    }

    // 通用可排序表格
    $('.l4d2-table.sortable').each(function () {
        if (!$.fn.DataTable.isDataTable(this)) {
            $(this).DataTable(dtDefaults);
        }
    });

    // ============================================================
    // Chart.js 圖表
    // ============================================================
    var playerColors = [
        'rgba(255, 68, 68, 0.8)',
        'rgba(68, 138, 255, 0.8)',
        'rgba(76, 175, 80, 0.8)',
        'rgba(255, 193, 7, 0.8)',
        'rgba(156, 39, 176, 0.8)',
        'rgba(0, 188, 212, 0.8)',
        'rgba(255, 87, 34, 0.8)',
        'rgba(139, 195, 74, 0.8)',
    ];

    if (typeof Chart !== 'undefined') {
        // 共用設定
        Chart.defaults.color = '#ccc';
        Chart.defaults.borderColor = '#333';

        // 玩家個人武器圖表
        renderBarChart('#l4d2-weapon-chart', 'weapons', '擊殺數', 'kills');

        // 全站武器圖表
        renderBarChart('#l4d2-global-weapon-chart', 'weapons', '總擊殺數', 'kills');

        // 戰役遊玩次數圖表
        renderBarChart('#l4d2-campaign-chart', 'campaigns', '遊玩次數', 'plays');

        // 場次詳細頁面圖表
        renderSessionBarChart('#l4d2-session-kills-chart', '擊殺數');
        renderSessionBarChart('#l4d2-session-damage-chart', '傷害量');
        renderSessionDoughnutChart('#l4d2-session-weapon-chart');
    }

    function renderBarChart(selector, dataAttr, label, dataKey) {
        var el = document.querySelector(selector);
        if (!el) return;

        var rawData = el.dataset[dataAttr] || el.dataset.weapons || el.dataset.campaigns;
        if (!rawData) return;

        var chartData;
        try {
            chartData = JSON.parse(rawData);
        } catch (e) {
            return;
        }

        var labels = chartData.labels || [];
        var values = chartData[dataKey] || chartData.kills || chartData.plays || [];

        if (labels.length === 0) return;

        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: label,
                        data: values,
                        backgroundColor: 'rgba(255, 68, 68, 0.7)',
                        borderColor: 'rgba(255, 68, 68, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#333' },
                        ticks: { color: '#999' },
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#999',
                            maxRotation: 45,
                            minRotation: 0,
                        },
                    },
                },
            },
        });
    }

    // ============================================================
    // 場次詳細頁面圖表
    // ============================================================
    function renderSessionBarChart(selector, label) {
        var el = document.querySelector(selector);
        if (!el) return;

        var chartData;
        try {
            chartData = JSON.parse(el.dataset.chart);
        } catch (e) {
            return;
        }

        if (!chartData.labels || chartData.labels.length === 0) return;

        var bgColors = chartData.labels.map(function (_, i) {
            return playerColors[i % playerColors.length];
        });

        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: label,
                    data: chartData.data,
                    backgroundColor: bgColors,
                    borderColor: bgColors.map(function (c) {
                        return c.replace('0.8', '1');
                    }),
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: '#333' },
                        ticks: { color: '#999' },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: '#ccc', font: { size: 14 } },
                    },
                },
            },
        });
    }

    function renderSessionDoughnutChart(selector) {
        var el = document.querySelector(selector);
        if (!el) return;

        var chartData;
        try {
            chartData = JSON.parse(el.dataset.chart);
        } catch (e) {
            return;
        }

        if (!chartData.labels || chartData.labels.length === 0) return;

        var doughnutColors = [
            'rgba(255, 68, 68, 0.85)',
            'rgba(68, 138, 255, 0.85)',
            'rgba(76, 175, 80, 0.85)',
            'rgba(255, 193, 7, 0.85)',
            'rgba(156, 39, 176, 0.85)',
            'rgba(0, 188, 212, 0.85)',
            'rgba(255, 87, 34, 0.85)',
            'rgba(139, 195, 74, 0.85)',
            'rgba(233, 30, 99, 0.85)',
            'rgba(121, 85, 72, 0.85)',
        ];

        new Chart(el.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: chartData.labels,
                datasets: [{
                    data: chartData.data,
                    backgroundColor: doughnutColors.slice(0, chartData.labels.length),
                    borderColor: '#1e1e1e',
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#ccc',
                            padding: 12,
                            font: { size: 12 },
                        },
                    },
                },
            },
        });
    }

    // ============================================================
    // 玩家搜尋 AJAX
    // ============================================================
    var searchTimer;
    var $searchInput = $('#l4d2-player-search-input');
    var $searchResults = $('#l4d2-search-results');

    if ($searchInput.length) {
        $searchInput.on('input', function () {
            clearTimeout(searchTimer);
            var query = $(this).val().trim();

            if (query.length < 2) {
                $searchResults.empty();
                return;
            }

            $searchResults.html('<div class="l4d2-search-loading">搜尋中...</div>');

            searchTimer = setTimeout(function () {
                $.post(
                    l4d2Stats.ajax_url,
                    {
                        action: 'l4d2_search_player',
                        nonce: l4d2Stats.nonce,
                        search: query,
                    },
                    function (response) {
                        if (!response.success) {
                            $searchResults.html(
                                '<div class="l4d2-notice">搜尋失敗</div>'
                            );
                            return;
                        }

                        var data = response.data;
                        if (!data || data.length === 0) {
                            $searchResults.html(
                                '<div class="l4d2-notice">找不到玩家</div>'
                            );
                            return;
                        }

                        var html = '';
                        data.forEach(function (p) {
                            var playerUrl =
                                l4d2Stats.player_page +
                                '?steam_id=' +
                                encodeURIComponent(p.steam_id);

                            html +=
                                '<div class="l4d2-search-result">' +
                                '<div>' +
                                '<a href="' + playerUrl + '">' +
                                escapeHtml(p.name) +
                                '</a>' +
                                '<br><span class="l4d2-search-steamid">' +
                                escapeHtml(p.steam_id) +
                                '</span>' +
                                '</div>' +
                                '<div class="l4d2-search-playtime">' +
                                formatPlaytime(parseInt(p.total_playtime) || 0) +
                                '</div>' +
                                '</div>';
                        });

                        $searchResults.html(html);
                    }
                );
            }, 300);
        });
    }

    // ============================================================
    // 工具函式
    // ============================================================
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatPlaytime(seconds) {
        if (seconds < 60) return '< 1 分鐘';
        var hours = Math.floor(seconds / 3600);
        var mins = Math.floor((seconds % 3600) / 60);
        if (hours > 0) {
            return hours + ' 小時 ' + mins + ' 分鐘';
        }
        return mins + ' 分鐘';
    }
});
