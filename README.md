# simple_php_webhook

Welcome to `simple_php_webhook` – the tool that's simple enough to set up and powerful enough to make you feel like a webhook wizard. This lightweight PHP-based webhook handler makes deploying changes via GitHub effortless for one or multiple projects.

## 🚀 Quick Setup Guide

1. **Clone the Repo**: First things first, clone this repo to your server.

2. **Configure**: We provide a configuration template (`config.json-template`). You'll need to make a copy of it and name it `config.json` in the main directory:
   ```sh
   cp config.json-template config.json
   ```
   Edit `config.json` to suit your needs. We'll walk through each setting below.

3. **Set up the Webhook Endpoint**:
   - Ensure that the `public` directory is accessible by your web server and the public internet. This is where GitHub will send those sweet, sweet webhook payloads.
   - The **main directory** should **not** be directly accessible via the web. It contains sensitive configuration details (like your `secret` key) and you wouldn't want the internet to see that, right?

4. **Point GitHub Webhook Here**: Set up a webhook in your GitHub repository to point to your server's publicly accessible URL (e.g., `https://your-domain.com/public/webhook_handler.php`). Don't forget to use the same `secret` key configured in `config.json`.

## 🔧 Configuration Values Explained

The configuration file (`config.json`) contains a few key settings that are critical to `simple_php_webhook`'s operation:

- **`secret`**: This is the shared secret key that must match between GitHub and this script. It's what keeps the bad guys out.

- **`log_file`**: The path to your log file where operational details get logged. Successes, failures, everything. Make sure the directory has the correct permissions for writing.

- **`projects`**: This is an array of projects, each with their own configuration:
  - **`github_repository`**: The GitHub repository name (e.g., `<Username>/<Project_name>`).
  - **`github_branch`**: The branch name you want to handle webhook requests for (`master`, etc.). Only requests from this branch will be processed.
  - **`project_path`**: The local path to the directory where the `git pull` command will be executed. This is where your code lives.

## 📝 Example Configuration

Here's a quick example of what `config.json` might look like:

```json
{
    "secret": "your_secret_token_here",
    "log_file": "logs/webhook_log.txt",
    "projects": [
        {
            "github_repository": "your_username/your_project",
            "github_branch": "main",
            "project_path": "/var/www/html/your_project"
        }
    ]
}
```

## 🌍 Deploying on WebSupport.sk

Personally, I use this project on hosting platforms like **WebSupport.sk**. If you're deploying there and need any help getting things set up, feel free to reach out – I'll be more than happy to help out.

Just keep in mind that the main directory must be kept safe, so it shouldn't be accessible directly from the internet. Only expose the `public` directory.

## ⚠️ Warning

This script is super simple, and that's its charm. But with great simplicity comes great responsibility. Make sure your `secret` is truly secret and that your server permissions are configured correctly. Otherwise, you might find yourself in a pickle when someone triggers an unintended `git pull`.

## 📞 Need Help?

If you encounter any issues or just want to chat about `simple_php_webhook` and how awesome it is, feel free to open an issue or contact me directly. Contributions are also welcome – let's make simple even simpler.

Happy Webhooking! 😎
