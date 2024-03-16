<!--
id: troubleshooting
tags: ''
-->

# Troubleshooting

There may be a bug with the autoload-dev where you will see a class not found after a --flush, but the class and namespace are correct.  If this happens it may be the wrong message, and due to an incorrect autoload path, such as one that doesn't exist. I can't replicate now, but maybe it will happen again.
