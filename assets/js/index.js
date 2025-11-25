function contactProfessor(profName) {
    alert("Contactez " + profName + " au : 01 23 45 67 89");
}

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function (position) {
        var userLat = position.coords.latitude;
        var userLng = position.coords.longitude;

        L.marker([userLat, userLng])
            .addTo(map)
            .bindPopup("Vous êtes ici !")
            .openPopup();
    });
}

fetch("../check_session.php")
    .then((response) => response.json())
    .then((data) => {
        if (data.loggedIn) {
            document.getElementById("welcome-name").textContent = data.prenom;
            document.getElementById("user-welcome").style.display = "block";
            document.querySelector(".auth-buttons").style.display = "none";
        }
    })
    .catch((error) => console.error("Erreur:", error));
const professors = [
    {
        id: 1,
        name: "Prof. Marie Dubois",
        subject: "Mathématiques",
        experience: "5 ans d'expérience",
        rating: 4.8,
        price: "30€/h",
        location: "Paris 15ème",
        description: "Spécialiste en algèbre et géométrie. Méthode pédagogique adaptée à chaque élève.",
        avatar: "https://randomuser.me/api/portraits/women/65.jpg",
        coordinates: [48.8584, 2.2945]
    },
    {
        id: 2,
        name: "Prof. Pierre Martin",
        subject: "Anglais",
        experience: "8 ans d'expérience",
        rating: 4.9,
        price: "35€/h",
        location: "Paris 4ème",
        description: "Professeur natif. Cours tous niveaux du débutant au confirmé.",
        avatar: "https://randomuser.me/api/portraits/men/32.jpg",
        coordinates: [48.8606, 2.3376]
    },
    {
        id: 3,
        name: "Prof. Sophie Laurent",
        subject: "Physique",
        experience: "6 ans d'expérience",
        rating: 4.7,
        price: "32€/h",
        location: "Paris 6ème",
        description: "Docteur en physique. Méthodologie adaptée avec expériences pratiques.",
        avatar: "https://randomuser.me/api/portraits/women/45.jpg",
        coordinates: [48.855, 2.3125]
    },
    {
        id: 4,
        name: "Prof. Jean Petit",
        subject: "Français",
        experience: "10 ans d'expérience",
        rating: 4.9,
        price: "28€/h",
        location: "Paris 5ème",
        description: "Ancien professeur de prépa. Spécialiste littérature française.",
        avatar: "https://randomuser.me/api/portraits/men/22.jpg",
        coordinates: [48.8525, 2.3508]
    },
    {
        id: 5,
        name: "Prof. Claire Moreau",
        subject: "Chimie",
        experience: "4 ans d'expérience",
        rating: 4.6,
        price: "31€/h",
        location: "Paris 13ème",
        description: "Ingénieure chimiste. Cours pratiques avec démonstrations.",
        avatar: "https://randomuser.me/api/portraits/women/33.jpg",
        coordinates: [48.8462, 2.345]
    },
    {
        id: 6,
        name: "Prof. Thomas Bernard",
        subject: "Anglais",
        experience: "7 ans d'expérience",
        rating: 4.8,
        price: "33€/h",
        location: "Paris 8ème",
        description: "Formateur en entreprise. Anglais des affaires et conversation.",
        avatar: "https://randomuser.me/api/portraits/men/45.jpg",
        coordinates: [48.872, 2.300]
    }
];

let map;
let markers = [];

function initMap() {
    map = L.map("map").setView([48.8566, 2.3522], 12);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "© OpenStreetMap contributors",
    }).addTo(map);

    professors.forEach(addProfessorToMap);
}

function addProfessorToMap(professor) {
    var customIcon = L.divIcon({
        className: "custom-marker",
        html: "",
        iconSize: [20, 20],
        iconAnchor: [10, 10],
    });

    var marker = L.marker(professor.coordinates, { icon: customIcon }).addTo(map);

    marker.bindPopup(`
        <div class="marker-popup">
            <h4>${professor.name}</h4>
            <p><strong>Matière:</strong> ${professor.subject}</p>
            <p><strong>Expérience:</strong> ${professor.experience}</p>
            <p><strong>Tarif:</strong> ${professor.price}</p>
            <button onclick="contactProfessor(${professor.id})"
                    style="background: #1898e9; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
                Contacter
            </button>
        </div>
    `);

    markers.push({
        professor: professor,
        marker: marker
    });
}

function searchProfessors(query) {
    const searchTerm = query.toLowerCase().trim();

    if (searchTerm === '') {
        return professors;
    }

    return professors.filter(professor =>
        professor.subject.toLowerCase().includes(searchTerm) ||
        professor.name.toLowerCase().includes(searchTerm) ||
        professor.description.toLowerCase().includes(searchTerm)
    );
}

function displayResults(results) {
    const resultsContainer = document.getElementById('results-container');
    const resultsSection = document.querySelector('.search-results');

    if (results.length === 0) {
        resultsContainer.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <h3>Aucun professeur trouvé</h3>
                <p>Essayez avec d'autres termes comme "Mathématiques", "Anglais", "Physique"...</p>
            </div>
        `;
    } else {
        resultsContainer.innerHTML = results.map(professor => `
            <div class="professor-card">
                <div class="professor-header">
                    <div class="professor-avatar">
                        <img src="${professor.avatar}" alt="${professor.name}">
                    </div>
                    <div class="professor-info">
                        <h3>${professor.name}</h3>
                        <span class="professor-subject">${professor.subject}</span>
                    </div>
                </div>
                <div class="professor-details">
                    <p><strong>Expérience:</strong> ${professor.experience}</p>
                    <p><strong>Localisation:</strong> ${professor.location}</p>
                    <p><strong>Tarif:</strong> ${professor.price}</p>
                    <div class="professor-rating">
                        ${generateStars(professor.rating)}
                        <span style="margin-left: 8px; color: #666;">(${professor.rating})</span>
                    </div>
                    <p>${professor.description}</p>
                </div>
                <button class="contact-button" onclick="contactProfessor(${professor.id})">
                    Contacter ce professeur
                </button>
            </div>
        `).join('');
    }

    resultsSection.style.display = 'block';

    updateMapMarkers(results);
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let stars = '';

    for (let i = 0; i < fullStars; i++) {
        stars += '<i class="fas fa-star"></i>';
    }

    if (hasHalfStar) {
        stars += '<i class="fas fa-star-half-alt"></i>';
    }

    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
        stars += '<i class="far fa-star"></i>';
    }

    return stars;
}

function updateMapMarkers(visibleProfessors) {
    markers.forEach(({ marker }) => {
        map.removeLayer(marker);
    });

    markers = [];

    visibleProfessors.forEach(professor => {
        addProfessorToMap(professor);
    });
}

function contactProfessor(professorId) {
    const professor = professors.find(p => p.id === professorId);
    if (professor) {
        alert(`Contactez ${professor.name} au : 01 23 45 67 89\nMatière: ${professor.subject}\nTarif: ${professor.price}`);
    }
}

function initGeolocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function (position) {
            var userLat = position.coords.latitude;
            var userLng = position.coords.longitude;

            L.marker([userLat, userLng])
                .addTo(map)
                .bindPopup("Vous êtes ici !")
                .openPopup();
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initMap();

    initGeolocation();

    const searchInput = document.querySelector('.search-input');
    const searchButton = document.querySelector('.search-button');

    searchButton.addEventListener('click', function () {
        console.log('Bouton de recherche cliqué');
        const results = searchProfessors(searchInput.value);
        console.log('Résultats trouvés:', results);
        displayResults(results);
    });

    searchInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            console.log('Recherche avec Entrée');
            const results = searchProfessors(searchInput.value);
            displayResults(results);
        }
    });

    fetch("check_session.php")
        .then((response) => response.json())
        .then((data) => {
            if (data.loggedIn) {
                document.getElementById("welcome-name").textContent = data.prenom;
                document.getElementById("user-welcome").style.display = "block";
                document.querySelector(".auth-buttons").style.display = "none";
            }
        })
        .catch((error) => console.error("Erreur:", error));
});

function selectTimeSlot(element) {
    document.querySelectorAll('.time-slot').forEach(slot => {
        slot.classList.remove('selected');
    });

    element.classList.add('selected');

    const timeText = element.querySelector('div').textContent;
    const dateText = element.querySelector('small').textContent;
    document.getElementById('selected-time').value = `${dateText} - ${timeText}`;
}

function takeAppointment() {
    const selectedTime = document.getElementById('selected-time').value;
    if (!selectedTime) {
        alert('Veuillez sélectionner un créneau horaire');
        return;
    }
    alert(`Rendez-vous pris pour : ${selectedTime}\nVous recevrez une confirmation par email.`);
}

function openFolder(folderName) {
    alert(`Ouverture du dossier : ${folderName}`);
}

function downloadDocument(button) {
    const documentName = button.closest('.document-card').querySelector('h6').textContent;
    alert(`Téléchargement de : ${documentName}`);
}

function shareDocument(button) {
    const documentName = button.closest('.document-card').querySelector('h6').textContent;
    alert(`Partage du document : ${documentName}`);
}

function showUploadModal() {
    document.getElementById('file-input').click();
}

function handleFileUpload(input) {
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        alert(`Fichier "${fileName}" prêt à être uploadé !`);
    }
}
