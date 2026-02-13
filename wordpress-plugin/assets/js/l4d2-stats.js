/**
 * L4D2 Player Stats - Frontend JavaScript
 * DataTables 初始化、Chart.js 圖表、AJAX 搜尋
 * 支援 PJAX (Argon Theme) 頁面切換後重新初始化
 */
(function ($) {

    // ============================================================
    // 共用設定
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

    // 追蹤已建立的 Chart 實例，PJAX 切換時銷毀
    var chartInstances = [];

    // ============================================================
    // 主要初始化函式（首次載入 + PJAX 切換後都會呼叫）
    // ============================================================
    function init() {
        initDataTables();
        initCharts();
        initSearch();
    }

    // ============================================================
    // DataTables
    // ============================================================
    function initDataTables() {
        initDT('#l4d2-leaderboard', { order: [[2, 'desc']] });
        initDT('#l4d2-weapons-table', { order: [[3, 'desc']] });
        initDT('#l4d2-sessions-table', { order: [[0, 'desc']], pageLength: 20 });
        initDT('#l4d2-session-players-table', {
            order: [[1, 'desc']], paging: false, searching: false, info: false,
        });
        initDT('#l4d2-session-weapons-table', { order: [[3, 'desc']] });

        $('.l4d2-table.sortable').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable(dtDefaults);
            }
        });
    }

    function initDT(selector, opts) {
        var $el = $(selector);
        if ($el.length && !$.fn.DataTable.isDataTable($el)) {
            $el.DataTable($.extend({}, dtDefaults, opts));
        }
    }

    // ============================================================
    // Chart.js 圖表
    // ============================================================
    function initCharts() {
        if (typeof Chart === 'undefined') return;

        // 銷毀之前的 Chart 實例（PJAX 切換時）
        chartInstances.forEach(function (c) { c.destroy(); });
        chartInstances = [];

        Chart.defaults.color = '#ccc';
        Chart.defaults.borderColor = '#333';

        renderBarChart('#l4d2-weapon-chart', 'weapons', '擊殺數', 'kills');
        renderBarChart('#l4d2-global-weapon-chart', 'weapons', '總擊殺數', 'kills');
        renderBarChart('#l4d2-campaign-chart', 'campaigns', '遊玩次數', 'plays');
        renderSessionBarChart('#l4d2-session-kills-chart', '擊殺數');
        renderSessionBarChart('#l4d2-session-damage-chart', '傷害量');
        renderSessionDoughnutChart('#l4d2-session-weapon-chart');
    }

    function fixChartContainer(el, height) {
        var container = el.parentNode;
        container.style.position = 'relative';
        container.style.height = height + 'px';
        container.style.overflow = 'hidden';
    }

    function renderBarChart(selector, dataAttr, label, dataKey) {
        var el = document.querySelector(selector);
        if (!el) return;

        var rawData = el.dataset[dataAttr] || el.dataset.weapons || el.dataset.campaigns;
        if (!rawData) return;

        var chartData;
        try { chartData = JSON.parse(rawData); } catch (e) { return; }

        var labels = chartData.labels || [];
        var values = chartData[dataKey] || chartData.kills || chartData.plays || [];
        if (labels.length === 0) return;

        fixChartContainer(el, 340);

        chartInstances.push(new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: values,
                    backgroundColor: 'rgba(255, 68, 68, 0.7)',
                    borderColor: 'rgba(255, 68, 68, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#333' },
                        ticks: { color: '#999' },
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#999', maxRotation: 45, minRotation: 0 },
                    },
                },
            },
        }));
    }

    function renderSessionBarChart(selector, label) {
        var el = document.querySelector(selector);
        if (!el) return;

        var chartData;
        try { chartData = JSON.parse(el.dataset.chart); } catch (e) { return; }
        if (!chartData.labels || chartData.labels.length === 0) return;

        fixChartContainer(el, 290);

        var bgColors = chartData.labels.map(function (_, i) {
            return playerColors[i % playerColors.length];
        });

        chartInstances.push(new Chart(el.getContext('2d'), {
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
                plugins: { legend: { display: false } },
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
        }));
    }

    function renderSessionDoughnutChart(selector) {
        var el = document.querySelector(selector);
        if (!el) return;

        var chartData;
        try { chartData = JSON.parse(el.dataset.chart); } catch (e) { return; }
        if (!chartData.labels || chartData.labels.length === 0) return;

        fixChartContainer(el, 340);

        chartInstances.push(new Chart(el.getContext('2d'), {
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
                        labels: { color: '#ccc', padding: 12, font: { size: 12 } },
                    },
                },
            },
        }));
    }

    // ============================================================
    // 玩家搜尋 (REST API)
    // ============================================================
    var searchTimer;

    function initSearch() {
        var $searchInput = $('#l4d2-player-search-input');
        var $searchResults = $('#l4d2-search-results');

        if (!$searchInput.length) return;

        // 避免 PJAX 重複綁定
        $searchInput.off('input.l4d2').on('input.l4d2', function () {
            clearTimeout(searchTimer);
            var query = $(this).val().trim();

            if (query.length < 2) {
                $searchResults.empty();
                return;
            }

            $searchResults.html('<div class="l4d2-search-loading">搜尋中...</div>');

            searchTimer = setTimeout(function () {
                $.ajax({
                    url: l4d2Stats.rest_url + 'search',
                    method: 'GET',
                    data: { q: query },
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', l4d2Stats.nonce);
                    },
                    success: function (data) {
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
                                encodeURIComponent(p.steam_id) +
                                '&from=search';

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
                    },
                    error: function () {
                        $searchResults.html(
                            '<div class="l4d2-notice">搜尋失敗</div>'
                        );
                    },
                });
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

    // ============================================================
    // 啟動：首次載入 + PJAX 切換
    // ============================================================
    $(document).ready(init);
    $(document).on('pjax:end', init);

})(jQuery);
