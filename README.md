# Build Hooks

## Description

This plugin allows you to trigger a build hook on CircleCI service.

### Requirements

### Terminus Secrets Plugin

You should set a secret key named `CIRCLE_CI_TOKEN` containing your CircleCI token value.

https://github.com/pantheon-systems/terminus-secrets-plugin

### Build metadata file

For local testing you should create a local file named `build-metadata.json` located at the root of your Wordpress site.

You can copy that file from your wordpress site from your pantheon project.

The file contains the following structure. The only required value is the `ref` key that contains the brach that will be used to build your static site from.

```json
{
  "url": "git@github.com:octahedroid/pantheon-proxy-wordpress.git",
  "ref": "master",
  "sha": "850fb6f225f47203f1a23ed6bc0f09864e3f0c1c",
  "comment": "fix: test build trigger",
  "commit-date": "2020-02-27 09:37:16 -0800",
  "build-date": "2020-02-27 17:42:13 +0000"
}
```
