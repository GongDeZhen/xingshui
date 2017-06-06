require 'mina/bundler'
require 'mina/rails'
require 'mina/git'
require 'mina/rvm'
# require 'mina/rbenv'  # for rbenv support. (http://rbenv.org)
# require 'mina/rvm'    # for rvm support. (http://rvm.io)

# Basic settings:
#   domain       - The hostname to SSH to.
#   deploy_to    - Path to deploy into.
#   repository   - Git repo to clone from. (needed by mina/git)
#   branch       - Branch name to deploy. (needed by mina/git)
if ENV['stage'].nil?
  puts 'Please specify a stage name to deploy'
  exit
end
ENV['stage'] ||= 'development'
load File.expand_path("../deploy/#{ENV['stage']}.rb", __FILE__)

set :repository, 'git@git.coding.net:bingtuanego/bingtuan-backend.git'

# For system-wide RVM install.
#   set :rvm_path, '/usr/local/rvm/bin/rvm'

# Manually create these paths in shared/ (eg: shared/config/database.yml) in your server.
# They will be linked in the 'deploy:link_shared_paths' step.
set :shared_paths, ['bingtuanego/conf','bingtuanego/adm/public/image','bingtuanego/adm/public/logs','bingtuanego/api/public/logs']

# Optional settings:
#   set :user, 'foobar'    # Username in the server to SSH to.
#   set :port, '30000'     # SSH port number.
#   set :forward_agent, true     # SSH forward_agent.

# This task is the environment that is loaded for most commands, such as
# `mina deploy` or `mina rake`.
task :environment do
  # If you're using rbenv, use this to load the rbenv environment.
  # Be sure to commit your .ruby-version or .rbenv-version to your repository.
  # invoke :'rbenv:load'

  # For those using RVM, use this to load an RVM version@gemset.
  # invoke :'rvm:use[ruby-2.4.0]'
end

# Put any custom mkdir's in here for when `mina setup` is ran.
# For Rails apps, we'll make some of the shared paths that are shared between
# all releases.
task :setup => :environment do
  queue! %[mkdir -p "#{deploy_to}/#{shared_path}"]
  queue! %[chmod g+rx,u+rwx "#{deploy_to}/#{shared_path}"]
end

desc "Deploys the current version to the server."
task :deploy => :environment do
  to :before_hook do
    # Put things to run locally before ssh
  end
  deploy do
    # Put things that will set up an empty directory into a fully set-up
    # instance of your project.
    invoke :'git:clone'
    invoke :'deploy:link_shared_paths'
    invoke :'deploy:cleanup'

    to :launch do
      queue "chown www:www -R #{deploy_to}/current/bingtuanego/adm/public/"
      queue "chown www:www -R #{deploy_to}/current/bingtuanego/api/public/"
      queue "phpstudy stop && sleep 3 && phpstudy start" if 'development' == ENV['stage']
      queue "systemctl restart php-fpm.service" if 'production' == ENV['stage'] && 'bt02' == ENV['domain']
    end
  end
end