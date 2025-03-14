import os
import zipfile

def zipdir(path, ziph):
    # ziph is zipfile handle
    for root, dirs, files in os.walk(path):
        if '.git' in dirs:
            dirs.remove('.git')
        for file in files:
            if file != '.gitignore':  # Skip .gitignore
                filepath = os.path.join(root, file)
                arcname = os.path.relpath(filepath, path)
                ziph.write(filepath, arcname)

if __name__ == '__main__':
    with zipfile.ZipFile('beepi-vehicle-lookup.zip', 'w', zipfile.ZIP_DEFLATED) as zipf:
        zipdir('clean-plugin', zipf)
    print("Created beepi-vehicle-lookup.zip")
