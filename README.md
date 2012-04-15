This is a mildly hacky app that lets users enter a postcode, which is then looked up for its 'ward code', which is then matched against a table of votes from the London 2008 Mayoral Election data.

At the moment it just shows first preference votes, although the data also includes second preference and London Member votes too.

It's not particularly smart (it's a PHP/MySQL app, I know they're not cool anymore) and it uses Google Charts API to render the data, but it works. I also had a go at some statistical analysis so I try to show where a particular ward strays from the average (currently using min/max of 75% and 120% to highlight differences).

Ideas, enhancements and feedback welcome!

One caveat: the data behind all this is fairly hefty (specifically the postcode lookup part), so if you want to run this yourself you'll need to be capable of dealing with 300mb+ SQL dumps (which I can't share on GitHub directly but can arrange if you contact me directly).