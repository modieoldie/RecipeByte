
//handles showing correct login/register form
function showForm(formId){
    const loginError = document.getElementById('login-error-message');
    const registerError = document.getElementById('register-error-message');


    //clearing existing error messages
    if(loginError) {
        loginError.textContent = '';
    }

    if(registerError){
        registerError.textContent = '';
    }

    //Hiding both forms by removing the 'active' class
    document.getElementById('login-form').classList.remove("active");
    document.getElementById('register-form').classList.remove("active");
    

    //Showing the target form by adding the 'active' class
    const targetForm = document.getElementById(formId);

    if(targetForm){
        targetForm.classList.add('active');
    }
}

//Code runs when the page loads to check for errors in the URL
window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(window.location.search);
    const errorType = params.get('error');
    const activeForm = params.get('form');
    
        // --- NEW: check for URL hash ---
    if(window.location.hash === "#signup") {
        showForm('register-form');
    } else if(window.location.hash === "#login") {
        showForm('login-form');
    }
    // --- END NEW ---


    //show correct form based on URL parameter
    if(activeForm){
        showForm(activeForm + '-form');
    }

    //Displays the correct error message
    if(errorType === 'login'){
        document.getElementById('login-error-message').textContent = 'Incorrect email or password.';
    } else if(errorType === 'emailnotfound') {
        document.getElementById('login-error-message').textContent = 'Email is not registered';
    } else if(errorType === 'register'){
        document.getElementById('register-error-message').textContent = 'Email is already registered.'; 
    } else if (errorType === 'passwordmismatch') {
        document.getElementById('register-error-message').textContent = 'Passwords does not match.';
    }

    if(errorType || activeForm){
        const cleanURL = window.location.protocol + "//" + window.location.host + window.location.pathname;

        window.history.replaceState({}, document.title, cleanURL);
    }

    //Logic for search bar toggle

    //getting reference to elements we need to work with
    const searchInput = document.getElementById('searchInput');
    const profileSearchIcon = document.getElementById('profileSearchIcon');
    const recipeSearchIcon = document.getElementById('recipeSearchIcon');

    //get form and the hidden input
    const searchForm = document.getElementById('heroSearchForm');
    const searchTypeInput = document.getElementById('searchType')


    //Making sure all elements were found on the page
    if(searchInput && profileSearchIcon && recipeSearchIcon && searchForm && searchTypeInput){

        //add a click event listener to the profile icon.
        //When user clicks the "Profile" icon to swtich to Profile search
        profileSearchIcon.addEventListener('click', () => {

            //switch to "user search" mode
            profileSearchIcon.classList.add('hidden');
            recipeSearchIcon.classList.remove('hidden');
            searchInput.placeholder = "Search for profiles";
            searchInput.focus();

            //change form action and hidden value
            searchForm.action = 'profile_search.php';
            searchTypeInput.value = 'profile';
        });

        //add a click event listener to the recipe icon
        //When user clicks the "Recipe" icon to swtich back to recipe search
        recipeSearchIcon.addEventListener('click', () => {

            //switch back to "recipe search" mode
            recipeSearchIcon.classList.add('hidden');
            profileSearchIcon.classList.remove('hidden');
            searchInput.placeholder = 'Search for recipes'
            searchInput.focus();

            //change form action and hidden value
            searchForm.action = 'home_page.php';
            searchTypeInput.value = 'recipe';
        });
    }

    const menuToggle = document.getElementById('menuToggle');
    const menuDropdown = document.getElementById('menuDropdown');

    if(menuToggle && menuDropdown){
        menuToggle.addEventListener('click', (event) => {
            menuDropdown.classList.toggle('show');
            event.stopPropagation();
        });

        document.addEventListener('click', (event) => {
            if(!menuDropdown.contains(event.target) && !menuToggle.contains(event.target)){
                menuDropdown.classList.remove('show');
            }
        });
    }
});