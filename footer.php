        </div> <!-- End .page-container -->

        <!-- Shared App Footer -->
        <footer class="app-footer">
            <div>
                <strong>GYM MANAGEMENT</strong> &bull; B.Tech DBMS Mini-Project
            </div>
            <div>
                Built with PHP, MySQL, HTML, CSS & JavaScript
            </div>
        </footer>
    </main> <!-- End .main-content -->
</div> <!-- End .app-container -->

<?php 
$is_user_dir = (basename(dirname($_SERVER['PHP_SELF'])) === 'user');
$script_path = $is_user_dir ? '../js/script.js' : 'js/script.js';
?>
<script src="<?php echo $script_path; ?>"></script>
</body>
</html>
