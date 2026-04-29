// ================================================
// MODAL MANAGEMENT
// ================================================

// Upload Modal
const uploadBtn = document.getElementById("uploadBtn");
const uploadModal = document.getElementById("uploadModal");
const closeUploadModal = document.getElementById("closeUploadModal");
const uploadForm = document.getElementById("uploadForm");

// Register Modal
const registerModal = document.getElementById("registerModal");
const closeRegisterModal = document.getElementById("closeRegisterModal");

// Open Upload Modal
if (uploadBtn) {
  uploadBtn.addEventListener("click", () => {
    uploadModal.style.display = "flex";
  });
}

// Close Upload Modal
if (closeUploadModal) {
  closeUploadModal.addEventListener("click", () => {
    uploadModal.style.display = "none";
  });
}

// Close Register Modal
if (closeRegisterModal) {
  closeRegisterModal.addEventListener("click", () => {
    registerModal.style.display = "none";
  });
}

// Close modal when clicking outside
window.addEventListener("click", (e) => {
  if (e.target === uploadModal) {
    uploadModal.style.display = "none";
  }
  if (e.target === registerModal) {
    registerModal.style.display = "none";
  }
});

// ================================================
// UPLOAD EVENT FORM SUBMISSION
// ================================================

if (uploadForm) {
  uploadForm.addEventListener("submit", async (e) => {
    e.preventDefault();

    // Collect form data
    const formData = new FormData();
    formData.append("title", document.getElementById("eventTitle").value);
    formData.append("category", document.getElementById("eventCategory").value);
    formData.append("club", document.getElementById("eventClub").value);
    formData.append("date", document.getElementById("eventDate").value);
    formData.append("time", document.getElementById("eventTime").value);
    formData.append("venue", document.getElementById("eventVenue").value);
    formData.append(
      "description",
      document.getElementById("eventDescription").value,
    );
    formData.append(
      "registration_link",
      document.getElementById("registrationLink").value,
    );
    formData.append("poster", document.getElementById("eventPoster").files[0]);

    try {
      const response = await fetch("upload_event.php", {
        method: "POST",
        body: formData,
      });

      const data = await response.json();

      if (data.success) {
        alert("Event uploaded successfully!");
        uploadForm.reset();
        uploadModal.style.display = "none";
        // Reload page or refresh events list
        location.reload();
      } else {
        alert("Error: " + data.message);
      }
    } catch (error) {
      console.error("Error:", error);
      alert("Failed to upload event. Please try again.");
    }
  });
}

// ================================================
// FILE UPLOAD DRAG & DROP
// ================================================

const fileInput = document.getElementById("eventPoster");
const fileUploadLabel = document.querySelector(".file-upload");

if (fileUploadLabel && fileInput) {
  // Prevent default drag behaviors
  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    fileUploadLabel.addEventListener(eventName, preventDefaults, false);
  });

  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }

  // Highlight drop area when item is dragged over it
  ["dragenter", "dragover"].forEach((eventName) => {
    fileUploadLabel.addEventListener(eventName, () => {
      fileUploadLabel.classList.add("drag-over");
    });
  });

  // Unhighlight drop area when item is dragged out of it
  ["dragleave", "drop"].forEach((eventName) => {
    fileUploadLabel.addEventListener(eventName, () => {
      fileUploadLabel.classList.remove("drag-over");
    });
  });

  // Handle dropped files
  fileUploadLabel.addEventListener("drop", (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files; // Set the file input files

    // Optional: show file name feedback
    if (files.length > 0) {
      const fileName = files[0].name;
      console.log("File selected:", fileName);
    }
  });
}

// ================================================
// VIEW TOGGLE (Grid/List View)
// ================================================

const gridViewBtn = document.getElementById("gridViewBtn");
const listViewBtn = document.getElementById("listViewBtn");
const eventsContainer = document.getElementById("eventsContainer");

if (gridViewBtn) {
  gridViewBtn.addEventListener("click", () => {
    eventsContainer.classList.remove("list-view");
    eventsContainer.classList.add("grid-view");
    gridViewBtn.classList.add("active");
    listViewBtn.classList.remove("active");
  });
}

if (listViewBtn) {
  listViewBtn.addEventListener("click", () => {
    eventsContainer.classList.remove("grid-view");
    eventsContainer.classList.add("list-view");
    listViewBtn.classList.add("active");
    gridViewBtn.classList.remove("active");
  });
}

// ================================================
// FILTER FUNCTIONALITY
// ================================================

const categoryFilter = document.getElementById("categoryFilter");
const clubFilter = document.getElementById("clubFilter");
const dateFilter = document.getElementById("dateFilter");
const resetBtn = document.getElementById("resetBtn");

function applyFilters() {
  const category = categoryFilter?.value || "";
  const club = clubFilter?.value || "";
  const date = dateFilter?.value || "";

  // Get all event cards
  const events = document.querySelectorAll(".event-card");

  events.forEach((event) => {
    let show = true;

    // Filter by category
    if (category && !event.dataset.category?.includes(category)) {
      show = false;
    }

    // Filter by club
    if (club && !event.dataset.club?.includes(club)) {
      show = false;
    }

    // Filter by date
    if (date) {
      const eventDate = new Date(event.dataset.date);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (date === "upcoming" && eventDate <= today) {
        show = false;
      } else if (date === "thisweek") {
        const weekEnd = new Date(today);
        weekEnd.setDate(weekEnd.getDate() + 7);
        if (eventDate < today || eventDate > weekEnd) {
          show = false;
        }
      } else if (date === "thismonth") {
        const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        if (eventDate < today || eventDate > monthEnd) {
          show = false;
        }
      }
    }

    event.style.display = show ? "block" : "none";
  });
}

if (categoryFilter) categoryFilter.addEventListener("change", applyFilters);
if (clubFilter) clubFilter.addEventListener("change", applyFilters);
if (dateFilter) dateFilter.addEventListener("change", applyFilters);

if (resetBtn) {
  resetBtn.addEventListener("click", () => {
    if (categoryFilter) categoryFilter.value = "";
    if (clubFilter) clubFilter.value = "";
    if (dateFilter) dateFilter.value = "";
    applyFilters();
  });
}

// ================================================
// EVENT REGISTRATION
// ================================================

function registerEvent(eventId, eventName, eventDate, eventTime, eventVenue) {
  document.getElementById("regEventName").textContent = eventName;
  document.getElementById("regEventDate").textContent = eventDate;
  document.getElementById("regEventTime").textContent = eventTime;
  document.getElementById("regEventVenue").textContent = eventVenue;
  registerModal.style.display = "flex";
}

// ================================================
// SAMPLE EVENTS (For testing, remove when backend integration is complete)
// ================================================

document.addEventListener("DOMContentLoaded", () => {
  // Load sample events if container is empty
  if (eventsContainer && eventsContainer.children.length === 0) {
    loadSampleEvents();
  }
});

function loadSampleEvents() {
  const sampleEvents = [
    {
      id: 1,
      title: "Web Development Workshop",
      category: "technical",
      club: "coding",
      date: "2024-04-15",
      time: "10:00 AM",
      venue: "Tech Lab",
      image: "uploads/posters/image.png",
      description: "Learn modern web development with React and Node.js",
    },
    {
      id: 2,
      title: "Annual Music Festival",
      category: "cultural",
      club: "music",
      date: "2024-04-20",
      time: "2:00 PM",
      venue: "Art Gallery",
      image: "uploads/posters/image.png",
      description:
        "Celebrate music with live performances from student artists",
    },
    {
      id: 3,
      title: "Inter-College Sports Championship",
      category: "sports",
      club: "sports",
      date: "2024-04-25",
      time: "3:00 PM",
      venue: "Sports Ground",
      image: "uploads/posters/image.png",
      description: "Compete in various sports events across colleges",
    },
  ];

  sampleEvents.forEach((event) => {
    const eventCard = createEventCard(event);
    eventsContainer.appendChild(eventCard);
  });
}

function createEventCard(event) {
  const card = document.createElement("div");
  card.className = "event-card";
  card.dataset.category = event.category;
  card.dataset.club = event.club;
  card.dataset.date = event.date;

  card.innerHTML = `
        <div class="event-image-container">
            <img src="${event.image}" alt="${event.title}" class="event-image" loading="lazy">
            <span class="category-badge">${event.category}</span>
        </div>
        <div class="event-info">
            <h3 class="event-title">${event.title}</h3>
            <p class="event-description">${event.description}</p>
            <div class="event-details">
                <span class="detail"><i class="far fa-calendar"></i> ${event.date}</span>
                <span class="detail"><i class="far fa-clock"></i> ${event.time}</span>
                <span class="detail"><i class="fas fa-map-marker-alt"></i> ${event.venue}</span>
            </div>
            <div class="event-meta">
                <span class="club-tag">${event.club}</span>
            </div>
        </div>
        <div class="event-actions">
            <button class="btn-secondary" onclick="registerEvent(${event.id}, '${event.title}', '${event.date}', '${event.time}', '${event.venue}')">
                Register
            </button>
        </div>
    `;

  // Add image error handler
  const imgElement = card.querySelector(".event-image");
  imgElement.addEventListener("error", function () {
    console.error(`Image failed to load: ${event.image}`);
    this.src =
      'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="100" height="100"%3E%3Crect fill="%23ddd" width="100" height="100"/%3E%3Ctext x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999" font-size="14"%3EImage Not Found%3C/text%3E%3C/svg%3E';
  });

  console.log(`Creating card for: ${event.title}, Image: ${event.image}`);
  return card;
}

// ================================================
// CANCEL EVENT REGISTRATION
// ================================================

function cancelEvent(eventId, eventName) {
  // Confirm with user
  if (
    !confirm(
      `Are you sure you want to cancel your registration for "${eventName}"?`,
    )
  ) {
    return;
  }

  // Create FormData
  const formData = new FormData();
  formData.append("event_id", eventId);

  // Send request
  fetch("cancel_registration.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        alert("Registration cancelled successfully!");

        // Find and remove the event card from the UI
        const eventCard = document.querySelector(
          `[data-event-id="${eventId}"]`,
        );
        if (eventCard) {
          eventCard.classList.add("fade-out");
          setTimeout(() => {
            eventCard.remove();
            // Check if there are any events left
            const remainingEvents =
              document.querySelectorAll(".event-list-item");
            if (remainingEvents.length === 0) {
              // Reload page to show empty state message
              location.reload();
            }
          }, 300);
        } else {
          // Fallback to reload if we can't find the event card
          location.reload();
        }
      } else {
        alert("Error: " + data.message);
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      alert("Failed to cancel registration. Please try again.");
    });
}
