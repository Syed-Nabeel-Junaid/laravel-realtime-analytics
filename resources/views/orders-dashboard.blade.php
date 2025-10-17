<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Orders Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Pusher JS -->
    <script src="https://js.pusher.com/8.4/pusher.min.js"></script>

    <style>
        .card.stats-card { min-height: 100px; text-align: center; }
        .popular-card { min-height: 120px; }
        .order-badge { font-size: 0.8rem; }
        #active-orders-list li { font-size: 0.9rem; }
    </style>
</head>
<body>
<div class="container my-4">
    <h2 class="mb-4">📊 Real-Time Orders Dashboard</h2>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card bg-light p-3">
                <h5>Total Orders</h5>
                <h3 id="total-orders">0</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-warning p-3 text-white">
                <h5>Pending Orders</h5>
                <h3 id="pending-orders">0</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-success p-3 text-white">
                <h5>Completed Orders</h5>
                <h3 id="completed-orders">0</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card bg-info p-3 text-white">
                <h5>Top Dish</h5>
                <h3 id="top-dish">-</h3>
            </div>
        </div>
    </div>

    <!-- Popular Dishes -->
    <h4 class="mb-3">Top 5 Popular Dishes</h4>
    <div class="row" id="popular-dishes-cards"></div>
    <canvas id="popularDishesChart" class="my-4" height="150"></canvas>

    <!-- Orders Over Time -->
    <h4 class="mb-3">Orders Over Time (Pending vs Completed)</h4>
    <canvas id="ordersChart" class="mb-4" height="150"></canvas>

    <!-- Delivery Time Stats -->
    <h4 class="mb-3">Delivery Time Stats</h4>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card p-3">
                <h6>Daily Average (minutes)</h6>
                <ul id="daily-delivery" class="list-group list-group-flush"></ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <h6>Weekly Average (minutes)</h6>
                <ul id="weekly-delivery" class="list-group list-group-flush"></ul>
            </div>
        </div>
    </div>

    <!-- Peak Ordering Hours -->
    <h4 class="mb-3">Peak Ordering Hours</h4>
    <ul id="peak-hours" class="list-group mb-4"></ul>

    <!-- Active Orders (>30 min) -->
    <h4 class="mb-3">Active Orders (&gt;30 min)</h4>
    <ul id="active-orders-list" class="list-group mb-4"></ul>
</div>

<script>
    console.log('Dashboard script loaded');

    Pusher.logToConsole = true;
    const pusher = new Pusher('{{ env("PUSHER_APP_KEY") }}', {
        cluster: '{{ env("PUSHER_APP_CLUSTER") }}',
        encrypted: true
    });
    const channel = pusher.subscribe('orders');

    // Stats
    let totalOrders = 0, pendingOrders = 0, completedOrders = 0;
    const dishCount = {};

    // Safe helper
    function safeField(data, field, fallback = '-') {
        return data && data[field] !== undefined && data[field] !== null ? data[field] : fallback;
    }

    // ✅ Updated updateStats function (fixed Top Dish issue)
 function updateStats(order, updateTotals = true) {
if (updateTotals) {
    const status = safeField(order, 'status', 'pending');

    totalOrders++;
    if (status === 'pending') pendingOrders++;
    if (status === 'completed') completedOrders++;

    document.getElementById('total-orders').textContent = totalOrders;
    document.getElementById('pending-orders').textContent = pendingOrders;
    document.getElementById('completed-orders').textContent = completedOrders;
}

        // ✅ Safely parse order items (handles JSON string or array)
        let items = [];
        try {
            if (Array.isArray(order.items)) {
                items = order.items;
            } else if (typeof order.items === 'string') {
                items = JSON.parse(order.items);
                if (typeof items[0] === 'string') {
                    items = items.map(i => JSON.parse(i));
                }
            }
        } catch (err) {
            console.warn('Error parsing order items:', err, order.items);
            items = [];
        }

        // ✅ Count dish quantities
        items.forEach(item => {
            const name = item.dish_name || `Dish #${item.dish_id}`;
            const qty = Number(item.qty) || 0;
            dishCount[name] = (dishCount[name] || 0) + qty;
        });

        // ✅ Find and display top-selling dish
        const topDishEntry = Object.entries(dishCount).sort((a, b) => b[1] - a[1])[0];
        document.getElementById('top-dish').textContent = topDishEntry ? topDishEntry[0] : '-';
    }

    // Orders Over Time Chart
    const ctxOrders = document.getElementById('ordersChart').getContext('2d');
    const ordersChart = new Chart(ctxOrders, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                { label: 'Pending', data: [], borderColor: 'orange', fill: false, tension: 0.2 },
                { label: 'Completed', data: [], borderColor: 'green', fill: false, tension: 0.2 }
            ]
        },
        options: { scales: { x: { title: { display: true, text: 'Time' } }, y: { beginAtZero: true, precision: 0 } } }
    });

    function updateChart(order) {
        const now = new Date().toLocaleTimeString();
        const status = safeField(order, 'status', 'pending');

        ordersChart.data.labels.push(now);
        ordersChart.data.datasets[0].data.push(status === 'pending' ? 1 : 0);
        ordersChart.data.datasets[1].data.push(status === 'completed' ? 1 : 0);

        if (ordersChart.data.labels.length > 10) {
            ordersChart.data.labels.shift();
            ordersChart.data.datasets[0].data.shift();
            ordersChart.data.datasets[1].data.shift();
        }
        ordersChart.update();
    }

    // Latest Orders
function addOrderToList(order) {
    let items = [];
    try {
        if (Array.isArray(order.items)) {
            items = order.items;
        } else if (typeof order.items === 'string') {
            items = JSON.parse(order.items);
            if (typeof items[0] === 'string') {
                items = items.map(i => JSON.parse(i));
            }
        }
    } catch (err) {
        console.warn('Error parsing order items:', err, order.items);
    }

    // ✅ Combine all dish names + qty
    const dishList = items.map(i => `${i.dish_name || `Dish #${i.dish_id}`} (${i.qty})`).join(', ');

    const orderHTML = `
        <div class="order-item">
            <strong>Order #${order.id}</strong> - ${dishList}<br>
            <span class="badge">${safeField(order, 'status', 'pending')}</span>
        </div>
    `;

    const container = document.getElementById('active-orders-list');
    container.insertAdjacentHTML('beforeend', orderHTML);
}


    // Popular Dishes
    async function fetchPopularDishes() {
        try {
            const response = await fetch('/api/orders/popular-dishes');
            const data = await response.json();

            const container = document.getElementById('popular-dishes-cards');
            container.innerHTML = '';

            if (!data || data.length === 0) {
                container.innerHTML = '<p>No dishes sold yet.</p>';
                return;
            }

            data.forEach(dish => {
                const card = document.createElement('div');
                card.className = 'col-md-2 mb-3';
                card.innerHTML = `<div class="card popular-card bg-light text-dark text-center p-2">
                    <h5>${dish.dish_name}</h5>
                    <p>Sold: ${dish.quantity_sold}</p>
                    <span class="badge bg-primary">Rank #${dish.rank}</span>
                </div>`;
                container.appendChild(card);
            });

            const ctxPopular = document.getElementById('popularDishesChart').getContext('2d');
            new Chart(ctxPopular, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.dish_name),
                    datasets: [{
                        label: 'Quantity Sold',
                        data: data.map(d => d.quantity_sold),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true, precision: 0 } } }
            });

        } catch (error) {
            console.error('Error fetching popular dishes:', error);
        }
    }

    // Delivery Times
    function updateDeliveryTimes(daily, weekly) {
        const dailyUl = document.getElementById('daily-delivery');
        dailyUl.innerHTML = '';
        Object.entries(daily).forEach(([date, avg]) => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${date}: ${avg} min`;
            dailyUl.appendChild(li);
        });

        const weeklyUl = document.getElementById('weekly-delivery');
        weeklyUl.innerHTML = '';
        Object.entries(weekly).forEach(([week, avg]) => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `${week}: ${avg} min`;
            weeklyUl.appendChild(li);
        });
    }

    // Peak Hours
    function updatePeakHours(hours) {
        const ul = document.getElementById('peak-hours');
        ul.innerHTML = '';
        hours.forEach(h => {
            const li = document.createElement('li');
            li.className = 'list-group-item';
            li.textContent = `Hour ${h.hour}: ${h.total_orders} orders`;
            ul.appendChild(li);
        });
    }

    // Fetch initial data
    async function fetchDashboard() {
        try {
            const response = await fetch('/api/dashboard');
            const data = await response.json();

            totalOrders = data.total_orders;
            pendingOrders = data.pending_orders;
            completedOrders = data.completed_orders;

            document.getElementById('total-orders').textContent = totalOrders;
            document.getElementById('pending-orders').textContent = pendingOrders;
            document.getElementById('completed-orders').textContent = completedOrders;

            // Active Orders
            document.getElementById('active-orders-list').innerHTML = '';
        data.active_orders.data.forEach(order => {
            addOrderToList(order);
            updateStats(order, false);
        });

            // Popular Dishes
            fetchPopularDishes();

            // Delivery Times
            updateDeliveryTimes(data.delivery_times.daily_avg_minutes, data.delivery_times.weekly_avg_minutes);

            // Peak Hours
            updatePeakHours(data.peak_hours);

            // Orders Chart (from active/pending/completed orders)
            data.active_orders.data.forEach(order => updateChart(order));
        } catch (err) {
            console.error('Error fetching dashboard:', err);
        }
    }

    fetchDashboard();

    // Real-time updates
    channel.bind('OrderCreated', function(order) {
        console.log('Real-time order received:', order);
        totalOrders++;
        if (order.status === 'pending') pendingOrders++;
        if (order.status === 'completed') completedOrders++;

        document.getElementById('total-orders').textContent = totalOrders;
        document.getElementById('pending-orders').textContent = pendingOrders;
        document.getElementById('completed-orders').textContent = completedOrders;

        addOrderToList(order, 'active-orders-list');
        updateStats(order);
        updateChart(order);
        fetchPopularDishes();
    });
</script>

</body>
</html>
