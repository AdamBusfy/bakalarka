import '../bootstrap.js';
import Chart from 'chart.js';

// const canvas = document.getElementById('canvas');
// const context = canvas.createContext('2d');

document.getElementById("items-card").onclick = function () {
    location.href = "/items";
};
document.getElementById("location-card").onclick = function () {
    location.href = "/locations";
};
document.getElementById("category-card").onclick = function () {
    location.href = "/categories";
};
document.getElementById("users-card").onclick = function () {
    location.href = "/users";
};

var ctx = document.getElementById('items_pie_chart').getContext('2d');

var assignedItems = document.getElementById('items_pie_chart').getAttribute('data-assigned-items');
var unassignedItems = document.getElementById('items_pie_chart').getAttribute('data-unassigned-items');
var deletedItems = document.getElementById('items_pie_chart').getAttribute('data-deleted-items');


var itemsPieChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Assigned items', 'Unassigned items', 'Deleted items'],
        datasets: [{
            label: '# of Votes',
            data: [assignedItems, unassignedItems, deletedItems],
            backgroundColor: [
                'rgba(75, 192, 192, 0.7)',
                'rgba(167,167,167,0.4)',
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(167,167,167, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            yAxes: [{
                display: false,
                ticks: {
                    beginAtZero: true
                }
            }]
        }
    }
});

console.log("HOMEPAGE");