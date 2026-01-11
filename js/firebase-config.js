import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-app.js";
import { getFirestore } from "https://www.gstatic.com/firebasejs/9.6.1/firebase-firestore.js";

// Your web app's Firebase configuration
const firebaseConfig = {
  apiKey: "FIREBASE_API_KEY_PLACEHOLDER",
  authDomain: "my-attendance-app-eb967.firebaseapp.com",
  projectId: "my-attendance-app-eb967",
  storageBucket: "my-attendance-app-eb967.appspot.com", // Corrected domain
  messagingSenderId: "831343304771",
  appId: "1:831343304771:web:178230fc87c93ba72a74f6"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// Export the database connection to use in other files
export { db };

