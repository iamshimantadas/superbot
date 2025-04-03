<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <?php wp_head(); ?>
    <?php wp_footer(); ?>
</head>

<body>
    <br><br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <form>
                    <div class="mb-3">
                        <label for="gemini-key" class="form-label">Google Gemini API Key</label>
                        <input type="password" class="form-control" id="gemini-key" placeholder="Enter Google Gemini API key">
                    </div>
                    <div class="mb-3">
                        <label for="contact-us-page" class="form-label">Contact Us Page Link</label>
                        <input type="text" class="form-control" id="contact-us-page" placeholder="Enter Contact Us Page Link">
                    </div>
                    <div class="mb-3">
                        <label for="business-name" class="form-label">Business Name</label>
                        <input type="text" class="form-control" id="business-name" placeholder="Enter Your Business Name">
                    </div>
                    <div class="mb-3">
                        <label for="business-description" class="form-label">Business Description</label>
                        <input type="text" class="form-control" id="business-description" placeholder="Enter Your Business Description">
                    </div>
                    <div class="mb-3">
                        <label for="restrictions" class="form-label">Restrictions (Any restrictions the bot should follow!)</label>
                        <textarea class="form-control" id="restrictions" rows="3"></textarea>
                    </div>
                    <button type="button" id="save-btn" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>

    
</body>
</html>