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

    // 追蹤圖表是否已初始化
    var chartsInitialized = false;

    // ============================================================
    // 主要初始化函式（首次載入 + PJAX 切換後都會呼叫）
    // ============================================================
    function init() {
        try { initDataTables(); } catch (e) { console.warn('[L4D2] DataTables init error:', e); }
        try { initCharts(); } catch (e) { console.warn('[L4D2] Charts init error:', e); }
        try { initSearch(); } catch (e) { console.warn('[L4D2] Search init error:', e); }
    }

    // ============================================================
    // DataTables
    // ============================================================
    function initDataTables() {
        if (!$.fn.DataTable) return;
        initDT('#l4d2-leaderboard', { order: [[2, 'desc']] });
        initDT('#l4d2-weapons-table', { order: [[3, 'desc']] });
        initDT('#l4d2-maps-table', { order: [[2, 'desc']] });
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
        console.log('[L4D2-DEBUG] initCharts() 被呼叫');
        console.log('[L4D2-DEBUG] Chart 是否存在:', typeof Chart !== 'undefined');
        console.log('[L4D2-DEBUG] chartsInitialized:', chartsInitialized);

        // Chart.js 尚未載入 → 跳過，由 window.load 觸發
        if (typeof Chart === 'undefined') {
            console.warn('[L4D2-DEBUG] ✗ Chart.js 尚未載入');
            return;
        }

        // 檢查頁面上是否有需要初始化的 canvas
        var allCanvas = document.querySelectorAll('[id^="l4d2-"][id$="-chart"]');
        console.log('[L4D2-DEBUG] 找到 canvas 數量:', allCanvas.length);
        if (allCanvas.length > 0) {
            allCanvas.forEach(function (c) {
                console.log('[L4D2-DEBUG]   canvas:', c.id,
                    '尺寸:', c.offsetWidth, 'x', c.offsetHeight,
                    '可見:', c.offsetParent !== null,
                    'data:', Object.keys(c.dataset));
            });
        }
        if (allCanvas.length === 0) return;

        // 銷毀之前的 Chart 實例（PJAX 切換時）
        chartInstances.forEach(function (c) { c.destroy(); });
        chartInstances = [];
        chartsInitialized = false;

        Chart.defaults.color = '#ccc';
        Chart.defaults.borderColor = '#333';

        renderBarChart('#l4d2-weapon-chart', 'weapons', '擊殺數', 'kills');
        renderBarChart('#l4d2-global-weapon-chart', 'weapons', '總擊殺數', 'kills');
        renderBarChart('#l4d2-campaign-chart', 'campaigns', '遊玩次數', 'plays');
        renderSessionBarChart('#l4d2-session-kills-chart', '擊殺數');
        renderSessionBarChart('#l4d2-session-damage-chart', '傷害量');
        renderSessionDoughnutChart('#l4d2-session-weapon-chart');

        chartsInitialized = chartInstances.length > 0;
        console.log('[L4D2-DEBUG] 圖表初始化完成, 建立了', chartInstances.length, '個圖表');
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
                aspectRatio: 2.5,
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
                aspectRatio: 1.8,
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
                aspectRatio: 2,
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
                            var sep = l4d2Stats.player_page.indexOf('?') !== -1 ? '&' : '?';
                            var playerUrl =
                                l4d2Stats.player_page +
                                sep + 'steam_id=' +
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

    // DOM ready
    $(document).ready(function () {
        console.log('[L4D2-DEBUG] === document.ready 觸發 ===');
        console.log('[L4D2-DEBUG] jQuery:', typeof jQuery, '| DataTable:', typeof $.fn.DataTable, '| Chart:', typeof Chart);
        try { initDataTables(); } catch (e) { console.warn('[L4D2] DataTables init error:', e); }
        try { initSearch(); } catch (e) { console.warn('[L4D2] Search init error:', e); }
        try { initCharts(); } catch (e) { console.warn('[L4D2] Charts init error:', e); }
    });

    // window load：所有外部資源（含 Chart.js CDN）保證已載入
    $(window).on('load', function () {
        console.log('[L4D2-DEBUG] === window.load 觸發 ===');
        console.log('[L4D2-DEBUG] Chart:', typeof Chart, '| chartsInitialized:', chartsInitialized);
        if (!chartsInitialized) {
            var hasCanvas = document.querySelector('[id^="l4d2-"][id$="-chart"]');
            console.log('[L4D2-DEBUG] 有 canvas:', !!hasCanvas);
            if (hasCanvas) {
                try { initCharts(); } catch (e) { console.warn('[L4D2] Charts init error on load:', e); }
            }
        }
    });

    // PJAX 切換：重新初始化所有元件
    $(document).on('pjax:end pjax:complete', function (e) {
        console.log('[L4D2-DEBUG] === PJAX 事件觸發:', e.type, '===');
        chartsInitialized = false;
        setTimeout(init, 100);
    });

})(jQuery);
