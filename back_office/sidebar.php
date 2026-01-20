<aside class="sidebar">
    <a href="?page=dashboard"><img src="front_end/assets/images/logo_Alizon.png" alt="Logo Alizon" width="270" height ="115"></a>

    <nav>
        <a href="?page=dashboard" <?php if($_GET['page']=='dashboard'){echo ('class="active"');}?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-display" viewBox="0 0 16 16">
                <path
                    d="M0 4s0-2 2-2h12s2 0 2 2v6s0 2-2 2h-4q0 1 .25 1.5H11a.5.5 0 0 1 0 1H5a.5.5 0 0 1 0-1h.75Q6 13 6 12H2s-2 0-2-2zm1.398-.855a.76.76 0 0 0-.254.302A1.5 1.5 0 0 0 1 4.01V10c0 .325.078.502.145.602q.105.156.302.254a1.5 1.5 0 0 0 .538.143L2.01 11H14c.325 0 .502-.078.602-.145a.76.76 0 0 0 .254-.302 1.5 1.5 0 0 0 .143-.538L15 9.99V4c0-.325-.078-.502-.145-.602a.76.76 0 0 0-.302-.254A1.5 1.5 0 0 0 13.99 3H2c-.325 0-.502.078-.602.145" />
            </svg> Tableau de bord</a>
        <a href="?page=remise" <?php if(isset($_GET['page']) && $_GET['page']=='remise'){echo ('class="active"');}?>><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-currency-euro" viewBox="0 0 16 16">
                <path
                    d="M4 9.42h1.063C5.4 12.323 7.317 14 10.34 14c.622 0 1.167-.068 1.659-.185v-1.3c-.484.119-1.045.17-1.659.17-2.1 0-3.455-1.198-3.775-3.264h4.017v-.928H6.497v-.936q-.002-.165.008-.329h4.078v-.927H6.618c.388-1.898 1.719-2.985 3.723-2.985.614 0 1.175.05 1.659.177V2.194A6.6 6.6 0 0 0 10.341 2c-2.928 0-4.82 1.569-5.244 4.3H4v.928h1.01v1.265H4v.928z" />
            </svg> Promotion/remise</a>
    </nav>
</aside>